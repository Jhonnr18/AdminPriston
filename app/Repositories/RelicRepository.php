<?php

namespace App\Repositories;

use App\Models\Audit\ConfigAudit;
use App\Models\GameServer\ReliquiaBonus;
use App\Models\GameServer\ReliquiaDef;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ConfigPublicationService;
use Throwable;

class RelicRepository
{
    private const MAX_BONUS_ROWS = 128;

    private const EVADE_BONUS_TYPE = 37;

    private const EVADE_CAP = 10.0;

    public function __construct(
        private readonly ValhallaDatabase $database,
        private readonly ConfigPublicationService $publication,
    ) {}

    public function lockedSlot(): int
    {
        return (int) config('valhalla.relic_locked_slot');
    }

    public function assertEditableSlot(int $slot): void
    {
        if ($slot === $this->lockedSlot()) {
            throw new DomainException('O slot 11 de relíquia está bloqueado pelo contrato do servidor.');
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        if ($this->database->usingFixtures()) {
            return [
                ['slot' => 0, 'name' => 'Foice do Babel', 'item' => 'RR101', 'remover' => '', 'enabled' => true, 'locked' => false, 'bonuses' => ['dano'], 'bonus_rows' => [['type' => 1, 'value' => 5.0]]],
                ['slot' => 4, 'name' => 'Capuz do Mokova', 'item' => 'RR105', 'remover' => '', 'enabled' => true, 'locked' => false, 'bonuses' => ['defesa + vida'], 'bonus_rows' => [['type' => 5, 'value' => 3.0], ['type' => 29, 'value' => 50.0]]],
                ['slot' => 10, 'name' => 'Asa do Midranda', 'item' => 'RR111', 'remover' => '', 'enabled' => true, 'locked' => false, 'bonuses' => ['resistências / evade'], 'bonus_rows' => [['type' => 19, 'value' => 5.0], ['type' => 37, 'value' => 2.5]]],
                ['slot' => 11, 'name' => 'Slot bloqueado', 'item' => '—', 'remover' => '', 'enabled' => false, 'locked' => true, 'bonuses' => [], 'bonus_rows' => []],
            ];
        }

        $labels = config('valhalla.relic_bonus_types');

        return ReliquiaDef::query()
            ->with('bonuses')
            ->orderBy('RelicIndex')
            ->get()
            ->map(function ($relic) use ($labels) {
                $bonuses = $relic->bonuses->map(function ($bonus) use ($labels) {
                    $type = (int) $bonus->BonusType;
                    $label = $labels[$type] ?? ('tipo '.$type);

                    return $label.' '.$bonus->Value;
                })->all();

                $bonusRows = $relic->bonuses->map(fn ($bonus) => [
                    'type' => (int) $bonus->BonusType,
                    'value' => (float) $bonus->Value,
                ])->values()->all();

                return [
                    'slot' => (int) $relic->RelicIndex,
                    'name' => (string) $relic->Name,
                    'item' => (string) $relic->ItemCode,
                    'remover' => (string) ($relic->RemoverCode ?? ''),
                    'enabled' => (int) $relic->Enabled === 1,
                    'locked' => $relic->isLockedSlot(),
                    'bonuses' => $bonuses,
                    'bonus_rows' => $bonusRows,
                ];
            })
            ->all();
    }

    /**
     * Cria ou atualiza a linha de ReliquiaDef de um slot editável.
     *
     * @return array<string, mixed>
     */
    public function updateDef(
        int $slot,
        string $name,
        string $itemCode,
        ?string $removerCode,
        bool $enabled,
        string $operator,
        ?string $ip,
    ): array {
        $this->assertEditableSlot($slot);

        if ($slot < 0 || $slot > 11) {
            throw new DomainException('RelicIndex deve estar entre 0 e 11.');
        }

        $name = trim($name);
        if ($name === '') {
            throw new DomainException('O nome da relíquia não pode ser vazio.');
        }
        if (strlen($name) > 63) {
            throw new DomainException('O nome da relíquia excede 63 bytes em UTF-8 (o servidor trunca silenciosamente acima disso) — reduza o texto.');
        }

        $itemCode = strtoupper(trim($itemCode));
        if ($itemCode === '') {
            throw new DomainException('O código do item não pode ser vazio.');
        }
        if (strlen($itemCode) > 10) {
            throw new DomainException('O código do item excede 10 caracteres.');
        }

        $removerCode = $removerCode !== null ? strtoupper(trim($removerCode)) : null;
        if ($removerCode === '') {
            $removerCode = null;
        }
        if ($removerCode !== null && strlen($removerCode) > 10) {
            throw new DomainException('O código do item de remoção excede 10 caracteres.');
        }

        if ($this->database->demo() || ! $this->database->gameserverOnline()) {
            throw new DomainException('GameServer indisponível. Edição de relíquia exige SQL Server.');
        }

        return DB::connection('gameserver')->transaction(function () use ($slot, $name, $itemCode, $removerCode, $enabled, $operator, $ip) {
            $existing = ReliquiaDef::query()->where('RelicIndex', $slot)->lockForUpdate()->first();

            $before = $existing ? [
                'RelicIndex' => (int) $existing->RelicIndex,
                'Enabled' => (int) $existing->Enabled,
                'Name' => (string) $existing->Name,
                'ItemCode' => (string) $existing->ItemCode,
                'RemoverCode' => $existing->RemoverCode,
            ] : null;

            $relic = ReliquiaDef::query()->updateOrCreate(
                ['RelicIndex' => $slot],
                [
                    'Enabled' => $enabled ? 1 : 0,
                    'Name' => $name,
                    'ItemCode' => $itemCode,
                    'RemoverCode' => $removerCode,
                ],
            );

            $after = [
                'RelicIndex' => (int) $relic->RelicIndex,
                'Enabled' => (int) $relic->Enabled,
                'Name' => (string) $relic->Name,
                'ItemCode' => (string) $relic->ItemCode,
                'RemoverCode' => $relic->RemoverCode,
            ];

            ConfigAudit::query()->create([
                'operator' => $operator,
                'ip' => $ip,
                'action' => $before === null ? 'relic_def.create' : 'relic_def.update',
                'resource' => 'ReliquiaDef',
                'target_key' => (string) $slot,
                'value_before' => $before !== null ? json_encode($before) : null,
                'value_after' => json_encode($after),
                'note' => null,
                'result' => 'ok',
            ]);

            $versionId = $this->publication->recordMutation(
                'relic_def',
                (string) $slot,
                $before ?? [],
                $after,
                $operator,
                $ip,
            );
            $this->queueReload('relic_def', $operator, $versionId);

            return [
                'slot' => (int) $relic->RelicIndex,
                'name' => (string) $relic->Name,
                'item' => (string) $relic->ItemCode,
                'remover' => (string) ($relic->RemoverCode ?? ''),
                'enabled' => (int) $relic->Enabled === 1,
                'locked' => $relic->isLockedSlot(),
            ];
        });
    }

    /**
     * Substitui o conjunto completo de bônus de um slot editável.
     *
     * @param  list<array{type: int|string, value: int|float|string}>  $bonuses  estado final completo dos bônus do slot
     * @return array{slot: int, bonuses: list<array{type: int, value: float}>}
     */
    public function updateBonuses(int $slot, array $bonuses, string $operator, ?string $ip): array
    {
        $this->assertEditableSlot($slot);

        if ($slot < 0 || $slot > 11) {
            throw new DomainException('RelicIndex deve estar entre 0 e 11.');
        }

        $normalized = [];
        foreach ($bonuses as $bonus) {
            $type = (int) ($bonus['type'] ?? 0);
            $value = (float) ($bonus['value'] ?? 0);

            if ($type < 1 || $type > 37) {
                throw new DomainException("Tipo de bônus inválido ({$type}) — deve estar entre 1 e 37.");
            }
            if (! is_finite($value) || $value <= 0) {
                throw new DomainException('Todo valor de bônus precisa ser um número finito maior que zero (o servidor descarta zero/negativo silenciosamente).');
            }
            if ($type === self::EVADE_BONUS_TYPE && $value > self::EVADE_CAP) {
                throw new DomainException('Evasão (tipo 37) não pode passar de 10.0 — o servidor descarta a linha inteira acima do cap.');
            }

            $normalized[$type] = $value;
        }

        if ($this->database->demo() || ! $this->database->gameserverOnline()) {
            throw new DomainException('GameServer indisponível. Edição de relíquia exige SQL Server.');
        }

        return DB::connection('gameserver')->transaction(function () use ($slot, $normalized, $operator, $ip) {
            $otherCount = ReliquiaBonus::query()->where('RelicIndex', '!=', $slot)->count();
            $totalAfter = $otherCount + count($normalized);
            if ($totalAfter > self::MAX_BONUS_ROWS) {
                throw new DomainException("Essa alteração levaria ReliquiaBonus a {$totalAfter} linhas, acima do limite global de ".self::MAX_BONUS_ROWS.' (o servidor para de carregar linhas excedentes).');
            }

            $current = ReliquiaBonus::query()->where('RelicIndex', $slot)->lockForUpdate()->get();
            $before = $current->map(fn ($bonus) => [
                'type' => (int) $bonus->BonusType,
                'value' => (float) $bonus->Value,
            ])->values()->all();

            $keepTypes = array_keys($normalized);
            $deleteQuery = ReliquiaBonus::query()->where('RelicIndex', $slot);
            if ($keepTypes !== []) {
                $deleteQuery->whereNotIn('BonusType', $keepTypes);
            }
            $deleteQuery->delete();

            foreach ($normalized as $type => $value) {
                ReliquiaBonus::query()->updateOrCreate(
                    ['RelicIndex' => $slot, 'BonusType' => $type],
                    ['Value' => $value],
                );
            }

            $after = collect($normalized)
                ->map(fn ($value, $type) => ['type' => (int) $type, 'value' => (float) $value])
                ->values()
                ->all();

            ConfigAudit::query()->create([
                'operator' => $operator,
                'ip' => $ip,
                'action' => 'relic_bonus.update',
                'resource' => 'ReliquiaBonus',
                'target_key' => (string) $slot,
                'value_before' => json_encode($before),
                'value_after' => json_encode($after),
                'note' => null,
                'result' => 'ok',
            ]);

            $versionId = $this->publication->recordMutation(
                'relic_bonus',
                (string) $slot,
                $before,
                $after,
                $operator,
                $ip,
            );
            $this->queueReload('relic_bonus', $operator, $versionId);

            return [
                'slot' => $slot,
                'bonuses' => $after,
            ];
        });
    }

    /**
     * Enfileira um pedido de reload pro poller do GameServer C++. Best-effort:
     * a config em si já foi gravada na transação principal, então uma falha
     * aqui não pode derrubar a escrita — o operador ainda pode rodar
     * /reload_relic manualmente.
     */
    private function queueReload(string $resource, string $operator, ?int $versionId = null): void
    {
        if ($versionId !== null) {
            try {
                $this->publication->queueReload($resource, $versionId, $operator);
                return;
            } catch (Throwable $e) {
                Log::warning('Falha ao enfileirar reload versionado de relíquia.', [
                    'resource' => $resource,
                    'version_id' => $versionId,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($this->publication->queueReloadBestEffort($resource, $operator) !== null) {
            return;
        }

        try {
            DB::connection('gameserver')->insert(
                "INSERT INTO PainelDB.dbo.ConfigReloadRequest (Resource, VersionID, RequestedBy, Status) VALUES (?, 0, ?, 'pending')",
                [$resource, $operator],
            );
        } catch (Throwable $e) {
            Log::warning('[RelicRepository] Falha ao enfileirar ConfigReloadRequest', [
                'resource' => $resource,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
