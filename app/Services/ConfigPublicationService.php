<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Orquestra versionamento, snapshot, lock e reload das configurações do Valhalla.
 *
 * O schema destas tabelas pertence à source C++ (docs/sql/20 e 21); este
 * serviço nunca cria ou altera tabelas externas.
 */
class ConfigPublicationService
{
    private const RESOURCES = [
        'rarity_group', 'rarity_chance', 'rarity_mod', 'rarity_bonus',
        'relic_def', 'relic_bonus', 'skill_ini', 'skill_sql', 'item_skin',
        'shop_coin',
    ];

    public function publish(
        string $resource,
        string $targetKey,
        array $before,
        array $after,
        string $operator,
        ?string $ip,
        callable $write,
        ?string $reason = null,
    ): array {
        $this->assertResource($resource);
        $lockToken = (string) Str::uuid();
        $versionId = null;

        try {
            $versionId = DB::connection('paineldb')->transaction(function () use (
                $resource, $targetKey, $before, $after, $operator, $ip, $write, $reason, $lockToken
            ): int {
                $this->acquireLock($resource, $operator, $lockToken);

                $version = DB::connection('paineldb')->table('ConfigVersion')->insertGetId([
                    'Resource' => $resource,
                    'VersionID' => 0,
                    'CreatedBy' => $operator,
                    'CreatedAt' => CarbonImmutable::now(),
                    'Reason' => $reason,
                ]);

                DB::connection('paineldb')->table('ConfigSnapshot')->insert([
                    'Resource' => $resource,
                    'VersionID' => $version,
                    'TargetKey' => $targetKey,
                    'SnapshotJson' => json_encode($before, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'CreatedBy' => $operator,
                    'CreatedAt' => CarbonImmutable::now(),
                ]);

                $write();

                DB::connection('paineldb')->table('valhalla_config_audit')->insert([
                    'Operator' => $operator,
                    'IpAddress' => $ip,
                    'Resource' => $resource,
                    'TargetKey' => $targetKey,
                    'ValueBefore' => json_encode($before, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'ValueAfter' => json_encode($after, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'Result' => 'published',
                    'Reason' => $reason,
                    'CreatedAt' => CarbonImmutable::now(),
                ]);

                return (int) $version;
            });

            $reloadId = $this->queueReload($resource, (int) $versionId, $operator);
            $this->releaseLock($resource, $lockToken);

            return ['version_id' => $versionId, 'reload_id' => $reloadId, 'status' => 'pending'];
        } catch (Throwable $e) {
            $this->releaseLock($resource, $lockToken);
            Log::error('Falha na publicação de configuração Valhalla.', [
                'resource' => $resource, 'target_key' => $targetKey, 'operator' => $operator,
                'exception' => $e,
            ]);
            throw new DomainException('A publicação não foi concluída. Verifique o schema do PainelDB e tente novamente.', 0, $e);
        }
    }

    public function queueReload(string $resource, int $versionId, string $operator): int
    {
        $this->assertResource($resource);

        $requestId = (int) DB::connection('paineldb')->table('ConfigReloadRequest')->insertGetId([
            'Resource' => $resource,
            'VersionID' => $versionId,
            'RequestedBy' => $operator,
            'Status' => 'pending',
            'RequestedAt' => CarbonImmutable::now(),
        ]);

        $this->requestServerReload($resource, $versionId, $operator, $requestId);

        return $requestId;
    }

    public function requestServerReload(
        string $resource,
        int $versionId,
        string $operator,
        ?int $requestId = null,
    ): bool {
        $this->assertResource($resource);
        $baseUrl = rtrim((string) config('valhalla.server_reload.url'), '/');
        $path = config('valhalla.server_reload.paths.'.$resource);

        if ($baseUrl === '' || ! is_string($path) || $path === '') {
            Log::warning('API de reload do servidor não configurada.', [
                'resource' => $resource,
                'version_id' => $versionId,
            ]);
            return false;
        }

        try {
            $request = Http::acceptJson()
                ->timeout((int) config('valhalla.server_reload.timeout', 5))
                ->withHeaders(['X-Panel-Operator' => $operator]);
            $token = (string) config('valhalla.server_reload.token');
            if ($token !== '') {
                $request = $request->withToken($token);
            }

            $response = $request->post($baseUrl.$path, [
                'resource' => $resource,
                'version_id' => $versionId,
                'request_id' => $requestId,
                'operator' => $operator,
            ]);
            $response->throw();

            return true;
        } catch (Throwable $e) {
            Log::warning('API de reload do servidor indisponível.', [
                'resource' => $resource,
                'version_id' => $versionId,
                'endpoint' => $baseUrl.$path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function queueReloadBestEffort(string $resource, string $operator): ?int
    {
        try {
            return $this->queueReload($resource, 0, $operator);
        } catch (Throwable $e) {
            Log::warning('PainelDB indisponível; reload legado será usado.', [
                'resource' => $resource,
                'error' => $e->getMessage(),
            ]);
            $this->requestServerReload($resource, 0, $operator);

            return null;
        }
    }

    /**
     * Registra uma alteração já aplicada no banco de domínio.
     *
     * O painel mantém a transação da configuração no GameServer separada da
     * transação de governança do PainelDB. Por isso esta operação é best-effort:
     * a configuração não deve ser revertida apenas porque o schema de
     * versionamento ainda não foi instalado no ambiente.
     */
    public function recordMutation(
        string $resource,
        string $targetKey,
        array $before,
        array $after,
        string $operator,
        ?string $ip,
        ?string $reason = null,
    ): ?int {
        $this->assertResource($resource);

        try {
            return (int) DB::connection('paineldb')->transaction(function () use (
                $resource, $targetKey, $before, $after, $operator, $ip, $reason
            ): int {
                $version = DB::connection('paineldb')->table('ConfigVersion')->insertGetId([
                    'Resource' => $resource,
                    'VersionID' => 0,
                    'CreatedBy' => $operator,
                    'CreatedAt' => CarbonImmutable::now(),
                    'Reason' => $reason,
                ]);

                $beforeJson = json_encode($before, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                $afterJson = json_encode($after, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

                DB::connection('paineldb')->table('ConfigSnapshot')->insert([
                    'Resource' => $resource,
                    'VersionID' => $version,
                    'TargetKey' => $targetKey,
                    'SnapshotJson' => $beforeJson,
                    'CreatedBy' => $operator,
                    'CreatedAt' => CarbonImmutable::now(),
                ]);

                DB::connection('paineldb')->table('valhalla_config_audit')->insert([
                    'Operator' => $operator,
                    'IpAddress' => $ip,
                    'Resource' => $resource,
                    'TargetKey' => $targetKey,
                    'ValueBefore' => $beforeJson,
                    'ValueAfter' => $afterJson,
                    'Result' => 'published',
                    'Reason' => $reason,
                    'CreatedAt' => CarbonImmutable::now(),
                ]);

                return (int) $version;
            });
        } catch (Throwable $e) {
            Log::warning('PainelDB indisponível; mutation seguirá sem versionamento externo.', [
                'resource' => $resource,
                'target_key' => $targetKey,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function recentReloads(int $limit = 25): array
    {
        return DB::connection('paineldb')->table('ConfigReloadRequest')
            ->orderByDesc('RequestedAt')->limit($limit)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function acquireLock(string $resource, string $operator, string $token): void
    {
        $db = DB::connection('paineldb');
        $existing = $db->table('ConfigLock')->where('Resource', $resource)->lockForUpdate()->first();
        if ($existing && $existing->ExpiresAt && CarbonImmutable::parse($existing->ExpiresAt)->isFuture()) {
            throw new DomainException('Este recurso está sendo editado por outro operador.');
        }

        $values = [
            'Resource' => $resource, 'LockToken' => $token, 'LockedBy' => $operator,
            'ExpiresAt' => CarbonImmutable::now()->addMinutes(5),
        ];
        $existing ? $db->table('ConfigLock')->where('Resource', $resource)->update($values)
                  : $db->table('ConfigLock')->insert($values);
    }

    private function releaseLock(string $resource, string $token): void
    {
        try {
            DB::connection('paineldb')->table('ConfigLock')
                ->where('Resource', $resource)->where('LockToken', $token)->delete();
        } catch (Throwable $e) {
            Log::warning('Não foi possível liberar lock de configuração.', ['resource' => $resource, 'exception' => $e]);
        }
    }

    private function assertResource(string $resource): void
    {
        if (! in_array($resource, self::RESOURCES, true)) {
            throw new DomainException('Resource de configuração não reconhecido pelo contrato Valhalla.');
        }
    }
}
