<?php

namespace App\Repositories;

use App\Models\Audit\ConfigAudit;
use App\Models\GameServer\GameItem;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ConfigPublicationService;
use Throwable;

/**
 * Troca de skin de item: reaproveita um código alfa já compilado no cliente
 * (existente em items.h) como aparência visual de outro item, sem mudar a
 * identidade/stats do item original — só a coluna SkinCode na tabela de
 * equipamento. Segue o mesmo molde de RelicRepository::updateDef (validação
 * de domínio, transação com lockForUpdate, auditoria e queueReload
 * best-effort).
 */
class ItemSkinRepository
{
    public function __construct(
        private readonly ValhallaDatabase $database,
        private readonly ItemsHRepository $itemsH,
        private readonly ClientAssetRepository $assets,
        private readonly ConfigPublicationService $publication,
    ) {}

    /**
     * @return list<array{code: string, name: string, folder: string, icon_url: ?string}>
     */
    public function availableSkinsFor(string $code): array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return [];
        }

        $targetFolders = $this->categoryFolders($code);
        if ($targetFolders === []) {
            // Não dá pra saber a categoria do item de origem (nem items.h, nem
            // prefixo conhecido) — não tem base segura pra sugerir candidatos.
            return [];
        }

        $out = [];
        foreach ($this->itemsH->catalog() as $entryCode => $entry) {
            if ($entryCode === $code) {
                continue;
            }
            $folder = $entry['folder'];
            if ($folder === null || ! in_array($folder, $targetFolders, true)) {
                continue;
            }
            $out[] = [
                'code' => $entryCode,
                'name' => $entry['name'],
                'folder' => $folder,
                'icon_url' => $this->assets->iconUrl($entryCode),
            ];
        }

        usort($out, fn (array $a, array $b) => $a['code'] <=> $b['code']);

        return $out;
    }

    /**
     * Skin ativa hoje (coluna SkinCode). Retorna null em modo demo/offline, se
     * o item não existir, ou se a coluna SkinCode ainda não existir na tabela
     * (schema ainda não migrado no source C++) — nunca lança exceção, é só
     * leitura auxiliar pra UI.
     */
    public function currentSkin(string $table, string $code): ?string
    {
        if (! in_array($table, config('valhalla.item_tables'), true)) {
            return null;
        }

        if ($this->database->usingFixtures()) {
            return null;
        }

        $code = strtoupper(trim($code));

        try {
            $value = GameItem::queryTable($table)->where('Code', $code)->value('SkinCode');

            return $value !== null && $value !== '' ? strtoupper((string) $value) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Aplica (ou remove, se $skinCode for null) a skin de um item.
     *
     * @return array<string, mixed>
     */
    public function setSkin(string $table, string $code, ?string $skinCode, string $operator, ?string $ip): array
    {
        if (! in_array($table, config('valhalla.item_tables'), true)) {
            throw new DomainException("Tabela de item inválida: {$table}.");
        }

        $code = strtoupper(trim($code));
        if ($code === '') {
            throw new DomainException('O código do item não pode ser vazio.');
        }

        $skinCode = $skinCode !== null ? strtoupper(trim($skinCode)) : null;
        if ($skinCode === '') {
            $skinCode = null;
        }

        $this->assertItemExists($table, $code);

        if ($skinCode !== null) {
            if ($skinCode === $code) {
                throw new DomainException('A skin não pode ser igual ao item original.');
            }
            if (strlen($skinCode) > 10) {
                throw new DomainException('O código da skin excede 10 caracteres.');
            }
            if (! isset($this->itemsH->catalog()[$skinCode])) {
                throw new DomainException("Código de skin {$skinCode} não existe em items.h — só é permitido reaproveitar skins já compiladas no cliente.");
            }

            $this->assertCompatibleCategory($code, $skinCode);
        }

        if ($this->database->usingFixtures()) {
            return $this->applySimulated($table, $code, $skinCode, $operator, $ip);
        }

        return $this->applyReal($table, $code, $skinCode, $operator, $ip);
    }

    private function assertItemExists(string $table, string $code): void
    {
        if ($this->database->usingFixtures()) {
            if (! isset($this->itemsH->catalog()[$code])) {
                throw new DomainException("Item {$code} não encontrado no catálogo (items.h).");
            }

            return;
        }

        $exists = GameItem::queryTable($table)->where('Code', $code)->exists();
        if (! $exists) {
            throw new DomainException("Item {$code} não encontrado na tabela {$table}.");
        }
    }

    /**
     * Recusa combinações de categoria óbvia (ex: skin de arma num anel). É só
     * uma camada de sanidade no painel — a validação final de verdade é no
     * servidor C++. Quando não dá pra determinar a categoria de um dos lados
     * (prefixo desconhecido e sem entrada em items.h), não bloqueia.
     */
    private function assertCompatibleCategory(string $code, string $skinCode): void
    {
        $origFolders = $this->categoryFolders($code);
        $skinFolders = $this->categoryFolders($skinCode);

        if ($origFolders === [] || $skinFolders === []) {
            return;
        }

        if (array_intersect($origFolders, $skinFolders) === []) {
            throw new DomainException(
                "Skin incompatível: {$code} é da categoria [".implode(', ', $origFolders).
                "] e {$skinCode} é da categoria [".implode(', ', $skinFolders).
                ']. Só é permitido trocar skin dentro da mesma categoria (ex: arma por arma, anel por anel).'
            );
        }
    }

    /**
     * @return list<string>
     */
    private function categoryFolders(string $code): array
    {
        $folders = [];
        $entry = $this->itemsH->catalog()[$code] ?? null;
        if ($entry && $entry['folder']) {
            $folders[] = $entry['folder'];
        }
        foreach ($this->assets->foldersForPrefix($code) as $folder) {
            $folders[] = $folder;
        }

        return array_values(array_unique($folders));
    }

    /**
     * Modo demo/GameServer offline: simula a operação sem tocar banco de
     * verdade (VALHALLA_DEMO_MODE=true é o padrão do projeto). Ainda registra
     * auditoria — a tabela valhalla_audits vive na conexão default (sqlite
     * local), independente do GameServer.
     *
     * @return array<string, mixed>
     */
    private function applySimulated(string $table, string $code, ?string $skinCode, string $operator, ?string $ip): array
    {
        try {
            ConfigAudit::query()->create([
                'operator' => $operator,
                'ip' => $ip,
                'action' => 'item_skin.update',
                'resource' => 'item_skin',
                'target_key' => "{$table}.{$code}",
                'value_before' => json_encode(['SkinCode' => null]),
                'value_after' => json_encode(['SkinCode' => $skinCode]),
                'note' => 'simulado (modo demo / GameServer offline)',
                'result' => 'simulated',
            ]);
        } catch (Throwable $e) {
            Log::warning('[ItemSkinRepository] Falha ao registrar auditoria simulada', [
                'error' => $e->getMessage(),
            ]);
        }

        $this->queueReload('item_skin', $operator);

        return $this->present($table, $code, null, $skinCode, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function applyReal(string $table, string $code, ?string $skinCode, string $operator, ?string $ip): array
    {
        return DB::connection('gameserver')->transaction(function () use ($table, $code, $skinCode, $operator, $ip) {
            $row = GameItem::queryTable($table)->where('Code', $code)->lockForUpdate()->first();
            if (! $row) {
                throw new DomainException("Item {$code} não encontrado na tabela {$table}.");
            }

            $before = $row->SkinCode ?? null;
            $before = $before !== null && $before !== '' ? strtoupper((string) $before) : null;

            try {
                GameItem::queryTable($table)->where('Code', $code)->update(['SkinCode' => $skinCode]);
            } catch (QueryException $e) {
                if (str_contains(strtolower($e->getMessage()), 'invalid column name')) {
                    throw new DomainException(
                        "A coluna SkinCode ainda não existe na tabela {$table} — aplicar migration ".
                        'docs/sql/22-item-skin-code-up.sql no source C++ primeiro.'
                    );
                }

                throw $e;
            }

            ConfigAudit::query()->create([
                'operator' => $operator,
                'ip' => $ip,
                'action' => 'item_skin.update',
                'resource' => 'item_skin',
                'target_key' => "{$table}.{$code}",
                'value_before' => json_encode(['SkinCode' => $before]),
                'value_after' => json_encode(['SkinCode' => $skinCode]),
                'note' => null,
                'result' => 'ok',
            ]);

            $this->queueReload('item_skin', $operator);

            return $this->present($table, $code, $before, $skinCode, false);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function present(string $table, string $code, ?string $before, ?string $after, bool $simulated): array
    {
        return [
            'table' => $table,
            'code' => $code,
            'icon_url' => $this->assets->iconUrl($code),
            'skin_before' => $before,
            'skin_before_name' => $before !== null ? ($this->itemsH->nameFor($before) ?? $before) : null,
            'skin_before_icon_url' => $before !== null ? $this->assets->iconUrl($before) : null,
            'skin_code' => $after,
            'skin_name' => $after !== null ? ($this->itemsH->nameFor($after) ?? $after) : null,
            'skin_icon_url' => $after !== null ? $this->assets->iconUrl($after) : null,
            'simulated' => $simulated,
        ];
    }

    /**
     * Enfileira um pedido de reload pro poller do GameServer C++. Best-effort:
     * a config em si já foi gravada (ou simulada) antes desta chamada, então
     * uma falha aqui não pode derrubar o resultado. O literal 'item_skin' é o
     * resource que o poller de ConfigReloadRequest reconhece pra disparar
     * GameServer::readItemsFromDB() (contrato combinado com o time do C++).
     */
    private function queueReload(string $resource, string $operator): void
    {
        if ($this->publication->queueReloadBestEffort($resource, $operator) !== null) {
            return;
        }

        try {
            DB::connection('gameserver')->insert(
                "INSERT INTO PainelDB.dbo.ConfigReloadRequest (Resource, VersionID, RequestedBy, Status) VALUES (?, 0, ?, 'pending')",
                [$resource, $operator],
            );
        } catch (Throwable $e) {
            Log::warning('[ItemSkinRepository] Falha ao enfileirar ConfigReloadRequest', [
                'resource' => $resource,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
