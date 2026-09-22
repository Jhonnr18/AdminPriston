<?php

namespace App\Repositories;

use App\Models\GameServer\DropItem;
use App\Models\GameServer\DropList;
use App\Models\GameServer\Monster;
use App\Support\DemoCatalog;
use Illuminate\Support\Facades\Cache;

class DropRepository
{
    public function __construct(
        private readonly ValhallaDatabase $database,
        private readonly ItemsHRepository $itemsH,
    ) {}

    /**
     * Tokens especiais do servidor: Gold (ouro) e Air (nada).
     * Demais tokens são códigos de item separados por espaço.
     *
     * @return list<array<string, mixed>>
     */
    public function parseItemTokens(string $items, int $goldMin = 0, int $goldMax = 0, array $names = []): array
    {
        $tokens = preg_split('/\s+/', trim($items), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parsed = [];

        foreach ($tokens as $token) {
            if (strcasecmp($token, 'Gold') === 0) {
                $parsed[] = [
                    'code' => 'Gold',
                    'name' => 'Gold',
                    'gold_min' => $goldMin,
                    'gold_max' => $goldMax,
                ];
                continue;
            }

            if (strcasecmp($token, 'Air') === 0) {
                $parsed[] = [
                    'code' => 'Air',
                    'name' => 'Nada',
                ];
                continue;
            }

            $code = strtoupper($token);
            $parsed[] = [
                'code' => $code,
                'name' => $names[$code] ?? $code,
            ];
        }

        return $parsed;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function monsters(): array
    {
        if ($this->database->usingFixtures()) {
            return DemoCatalog::monsters();
        }

        return Cache::remember('valhalla.monster_drop_index', 60, function () {
            $drops = DropList::query()->get(['MonsterName', 'DropID'])->keyBy('MonsterName');
            $rowCounts = DropItem::query()
                ->selectRaw('DropID, COUNT(*) as qty')
                ->groupBy('DropID')
                ->pluck('qty', 'DropID');

            return Monster::query()
                ->orderBy('MonsterName')
                ->get(['MonsterName', 'MonsterLevel', 'Boss', 'Active'])
                ->map(function ($monster) use ($drops, $rowCounts) {
                    $drop = $drops->get($monster->MonsterName);

                    return [
                        'name' => (string) $monster->MonsterName,
                        'level' => $monster->MonsterLevel ?? '?',
                        'drop_id' => $drop->DropID ?? null,
                        'rows' => $drop ? (int) ($rowCounts[$drop->DropID] ?? 0) : 0,
                        'boss' => (int) ($monster->Boss ?? 0) === 1,
                        'active' => (int) ($monster->Active ?? 1) === 1,
                        'shared' => 0,
                    ];
                })
                ->all();
        });
    }

    /**
     * @return array{meta: array<string, mixed>, rows: list<array<string, mixed>>, shared_with: int}
     */
    public function monsterDrop(string $name): array
    {
        if ($this->database->usingFixtures()) {
            $meta = collect(DemoCatalog::monsters())->firstWhere('name', $name) ?? [
                'name' => $name, 'level' => '?', 'drop_id' => '?', 'rows' => 0, 'boss' => false,
            ];

            return [
                'meta' => $meta,
                'rows' => DemoCatalog::dropRows($name),
                'shared_with' => 1,
                'chance_sum' => collect(DemoCatalog::dropRows($name))->sum('chance'),
            ];
        }

        $monster = Monster::query()->where('MonsterName', $name)->first();
        $drop = DropList::query()->where('MonsterName', $name)->first();
        $shared = 1;
        if ($drop) {
            $shared = DropList::query()->where('DropID', $drop->DropID)->count();
        }

        $rows = [];
        if ($drop) {
            $rows = DropItem::query()
                ->where('DropID', $drop->DropID)
                ->orderBy('ID')
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->ID,
                    'chance' => (int) $row->Chance,
                    'items' => $this->parseItemTokens(
                        (string) $row->Items,
                        (int) ($row->GoldMin ?? 0),
                        (int) ($row->GoldMax ?? 0),
                    ),
                ])
                ->all();
        }

        $header = $this->itemsH->catalog();
        $rows = array_map(function (array $row) use ($header) {
            $row['items'] = array_map(function (array $item) use ($header) {
                $code = strtoupper((string) ($item['code'] ?? ''));
                if (isset($header[$code]['name'])) {
                    $item['name'] = $header[$code]['name'];
                }

                return $item;
            }, $row['items']);

            return $row;
        }, $rows);

        $stats = [];
        if ($monster) {
            foreach ([
                'Life', 'AttackPowerMin', 'AttackPowerMax', 'AttackRating', 'AttackSpeed', 'AttackRange',
                'Defense', 'Absorption', 'Block', 'MoveSpeed', 'Experience', 'ViewRange',
                'OrganicResistance', 'IceResistance', 'FireResistance', 'PoisonResistance', 'LightingResistance',
            ] as $key) {
                if ($monster->{$key} !== null && $monster->{$key} !== '') {
                    $stats[$key] = $monster->{$key};
                }
            }
        }

        return [
            'meta' => [
                'name' => $name,
                'level' => $monster?->MonsterLevel ?? '?',
                'drop_id' => $drop?->DropID ?? '—',
                'rows' => count($rows),
                'boss' => (int) ($monster?->Boss ?? 0) === 1,
                'active' => (int) ($monster?->Active ?? 1) === 1,
                'model' => $monster?->Model,
                'quantity' => $drop?->Quantity,
                'public' => (int) ($drop?->PublicDrop ?? 0) === 1,
                'stats' => $stats,
            ],
            'rows' => $rows,
            'shared_with' => $shared,
            'chance_sum' => array_sum(array_column($rows, 'chance')),
        ];
    }

    public function monsterCount(): int
    {
        if ($this->database->usingFixtures()) {
            return count(DemoCatalog::monsters());
        }

        return Monster::query()->count();
    }
}
