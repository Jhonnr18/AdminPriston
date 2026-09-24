<?php

namespace App\Repositories;

use App\Models\Audit\ConfigAudit;
use App\Models\GameServer\RarityChance;
use App\Models\GameServer\RarityChanceGroup;
use App\Models\GameServer\RarityChanceMod;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ConfigPublicationService;
use Throwable;

class RarityRepository
{
    public function __construct(
        private readonly ValhallaDatabase $database,
        private readonly ConfigPublicationService $publication,
    ) {}

    public function denominator(): int
    {
        return (int) config('valhalla.rarity_denominator');
    }

    /**
     * Common é o resto implícito: denominador − soma dos quatro tiers.
     *
     * @param  array<int, int>  $chances  rarity => chance
     * @return array{common: int, sum: int, overflow: bool, chances: array<int, int>}
     */
    public function remainder(array $chances): array
    {
        $sum = 0;
        foreach ([2, 3, 4, 5] as $rarity) {
            $sum += max(0, (int) ($chances[$rarity] ?? 0));
        }

        $denominator = $this->denominator();
        $overflow = $sum > $denominator;
        $common = $overflow ? 0 : $denominator - $sum;

        return [
            'common' => $common,
            'sum' => $sum,
            'overflow' => $overflow,
            'chances' => $chances,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function groups(): array
    {
        if ($this->database->usingFixtures()) {
            return [];
        }

        return RarityChanceGroup::query()
            ->with('chances')
            ->orderBy('ID')
            ->get()
            ->map(function ($group) {
                $chances = [];
                foreach ($group->chances as $row) {
                    $chances[(int) $row->Rarity] = (int) $row->Chance;
                }

                $math = $this->remainder($chances);

                return [
                    'id' => $group->ID,
                    'min' => $group->MinLevel,
                    'max' => $group->MaxLevel,
                    'uncommon' => $chances[2] ?? 0,
                    'rare' => $chances[3] ?? 0,
                    'epic' => $chances[4] ?? 0,
                    'legendary' => $chances[5] ?? 0,
                    'common' => $math['common'],
                    'overflow' => $math['overflow'],
                ];
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function modifiers(): array
    {
        if ($this->database->usingFixtures()) {
            return [];
        }

        return RarityChanceMod::query()
            ->orderBy('Type')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->Type,
                'common' => $row->ModCommon,
                'uncommon' => $row->ModUncommon,
                'rare' => $row->ModRare,
                'epic' => $row->ModEpic,
                'legendary' => $row->ModLegendary,
            ])
            ->all();
    }

    /**
     * Atualiza faixa de nível e as 4 chances editáveis de um grupo.
     * Common continua sendo o resto implícito (denominator − soma).
     *
     * @param  array{uncommon: int, rare: int, epic: int, legendary: int}  $chances
     * @return array<string, mixed>
     */
    public function updateGroup(int $groupId, int $minLevel, int $maxLevel, array $chances, string $operator, ?string $ip): array
    {
        if ($minLevel > $maxLevel) {
            throw new DomainException('MinLevel não pode ser maior que MaxLevel.');
        }

        $rarityMap = ['uncommon' => 2, 'rare' => 3, 'epic' => 4, 'legendary' => 5];

        $sum = 0;
        foreach ($rarityMap as $key => $rarity) {
            $value = (int) ($chances[$key] ?? 0);
            if ($value < 0) {
                throw new DomainException("A chance de {$key} não pode ser negativa.");
            }
            $sum += $value;
        }

        $denominator = $this->denominator();
        if ($sum >= $denominator) {
            throw new DomainException(
                'A soma das chances (Uncommon+Rare+Epic+Legendary) deve ser menor que '
                .number_format($denominator, 0, ',', '.').'. O servidor descarta o grupo inteiro se isso não for respeitado.'
            );
        }

        $overlap = RarityChanceGroup::query()
            ->where('ID', '!=', $groupId)
            ->where('MinLevel', '<=', $maxLevel)
            ->where('MaxLevel', '>=', $minLevel)
            ->exists();
        if ($overlap) {
            throw new DomainException('Essa faixa de nível sobrepõe outro grupo já cadastrado.');
        }

        return DB::connection('gameserver')->transaction(function () use ($groupId, $minLevel, $maxLevel, $chances, $rarityMap, $denominator, $operator, $ip) {
            $group = RarityChanceGroup::query()->where('ID', $groupId)->lockForUpdate()->first();
            if (! $group) {
                throw new DomainException('Grupo de raridade não encontrado.');
            }

            $before = [
                'id' => $group->ID,
                'min' => $group->MinLevel,
                'max' => $group->MaxLevel,
                'chances' => RarityChance::query()->where('RarityChanceGroup', $groupId)->pluck('Chance', 'Rarity')->all(),
            ];

            $group->MinLevel = $minLevel;
            $group->MaxLevel = $maxLevel;
            $group->save();

            $afterChances = [];
            foreach ($rarityMap as $key => $rarity) {
                $value = (int) ($chances[$key] ?? 0);
                RarityChance::query()->updateOrCreate(
                    ['RarityChanceGroup' => $groupId, 'Rarity' => $rarity],
                    ['Chance' => $value]
                );
                $afterChances[$rarity] = $value;
            }

            $after = [
                'id' => $groupId,
                'min' => $minLevel,
                'max' => $maxLevel,
                'chances' => $afterChances,
            ];

            ConfigAudit::query()->create([
                'operator' => $operator,
                'ip' => $ip,
                'action' => 'update',
                'resource' => 'RarityChanceGroup',
                'target_key' => (string) $groupId,
                'value_before' => json_encode($before),
                'value_after' => json_encode($after),
                'result' => 'ok',
            ]);

            $versionId = $this->publication->recordMutation(
                'rarity_group',
                (string) $groupId,
                $before,
                $after,
                $operator,
                $ip,
            );
            $this->enqueueReload('rarity_group', $operator, $versionId);

            $sum = array_sum($afterChances);

            return [
                'id' => $groupId,
                'min' => $minLevel,
                'max' => $maxLevel,
                'uncommon' => $afterChances[2],
                'rare' => $afterChances[3],
                'epic' => $afterChances[4],
                'legendary' => $afterChances[5],
                'common' => $denominator - $sum,
                'overflow' => false,
            ];
        });
    }

    /**
     * Atualiza os 5 multiplicadores de um modificador de raridade existente
     * (Type 1=Boss, 2=EventMimic, 3=EventMimicHighLevel, 4=BossHighLevel).
     * Não cria/apaga linhas, só edita as 5 colunas de uma linha existente.
     *
     * @param  array{common: float, uncommon: float, rare: float, epic: float, legendary: float}  $mods
     * @return array<string, mixed>
     */
    public function updateMod(int $type, array $mods, string $operator, ?string $ip): array
    {
        $modMap = [
            'common' => 'ModCommon',
            'uncommon' => 'ModUncommon',
            'rare' => 'ModRare',
            'epic' => 'ModEpic',
            'legendary' => 'ModLegendary',
        ];

        foreach ($modMap as $key => $column) {
            $value = $mods[$key] ?? null;
            if ($value === null || ! is_finite((float) $value) || (float) $value <= 0) {
                throw new DomainException("O modificador de {$key} deve ser um número finito maior que zero.");
            }
        }

        return DB::connection('gameserver')->transaction(function () use ($type, $mods, $modMap, $operator, $ip) {
            $mod = RarityChanceMod::query()->where('Type', $type)->lockForUpdate()->first();
            if (! $mod) {
                throw new DomainException('Modificador não encontrado.');
            }

            $before = [
                'type' => $mod->Type,
                'common' => $mod->ModCommon,
                'uncommon' => $mod->ModUncommon,
                'rare' => $mod->ModRare,
                'epic' => $mod->ModEpic,
                'legendary' => $mod->ModLegendary,
            ];

            foreach ($modMap as $key => $column) {
                $mod->{$column} = (float) $mods[$key];
            }
            $mod->save();

            $after = [
                'type' => $mod->Type,
                'common' => $mod->ModCommon,
                'uncommon' => $mod->ModUncommon,
                'rare' => $mod->ModRare,
                'epic' => $mod->ModEpic,
                'legendary' => $mod->ModLegendary,
            ];

            ConfigAudit::query()->create([
                'operator' => $operator,
                'ip' => $ip,
                'action' => 'update',
                'resource' => 'RarityChanceMod',
                'target_key' => (string) $type,
                'value_before' => json_encode($before),
                'value_after' => json_encode($after),
                'result' => 'ok',
            ]);

            $versionId = $this->publication->recordMutation(
                'rarity_mod',
                (string) $type,
                $before,
                $after,
                $operator,
                $ip,
            );
            $this->enqueueReload('rarity_mod', $operator, $versionId);

            return $after;
        });
    }

    /**
     * Enfileira pedido de reload pro servidor C++ consumir por polling
     * (PainelDB.dbo.ConfigReloadRequest, mesma instância SQL Server que GameServer).
     * Falha aqui é só logada — não pode derrubar a transação de escrita da config,
     * já que o operador ainda pode rodar o reload manual como hoje.
     */
    private function enqueueReload(string $resource, string $operator, ?int $versionId = null): void
    {
        if ($versionId !== null) {
            try {
                $this->publication->queueReload($resource, $versionId, $operator);
                return;
            } catch (Throwable $e) {
                Log::warning('Falha ao enfileirar reload versionado de raridade.', [
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
                [$resource, $operator]
            );
        } catch (Throwable $e) {
            Log::warning('Falha ao enfileirar pedido de reload de config.', [
                'resource' => $resource,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
