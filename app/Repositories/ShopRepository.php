<?php

namespace App\Repositories;

use App\Models\GameServer\Npc;
use App\Models\GameServer\NpcSellList;
use Illuminate\Support\Facades\Cache;

class ShopRepository
{
    public function __construct(
        private readonly ValhallaDatabase $database,
        private readonly ItemsHRepository $itemsH,
    ) {}

    /**
     * Lista leve para o menu lateral — sem carregar ItemList/nameMap.
     *
     * @return list<array<string, mixed>>
     */
    public function npcIndex(): array
    {
        if ($this->database->usingFixtures()) {
            return [[
                'id' => 3,
                'name' => 'NPC demo',
                'sell_id' => 3,
                'shared' => 1,
            ]];
        }

        return Cache::remember('valhalla.npc_index', 60, function () {
            $shared = Npc::query()
                ->selectRaw('SellID, COUNT(*) as qty')
                ->groupBy('SellID')
                ->pluck('qty', 'SellID');

            return Npc::query()
                ->orderBy('Name')
                ->get(['UniqueID', 'Name', 'Model', 'SellID', 'Code'])
                ->map(fn ($npc) => [
                    'id' => $npc->UniqueID,
                    'name' => (string) ($npc->Name ?? $npc->Model ?? $npc->UniqueID),
                    'model' => $npc->Model ?? null,
                    'code' => $npc->Code ?? null,
                    'sell_id' => $npc->SellID,
                    'shared' => (int) ($shared[$npc->SellID] ?? 1),
                ])
                ->all();
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public function npcDetail(int|string $id): ?array
    {
        $index = collect($this->npcIndex());
        $base = $index->firstWhere('id', $id) ?? $index->first();
        if (! $base) {
            return null;
        }

        if ($this->database->usingFixtures()) {
            $base['tabs'] = [
                1 => [['code' => 'WA108', 'name' => 'WA108']],
            ];
            $base['sell_types'] = config('valhalla.npc_sell_types');
            $base['item_count'] = 1;
            $base['active'] = true;

            return $base;
        }

        $header = $this->itemsH->catalog();
        $names = [];
        foreach ($header as $code => $entry) {
            $names[$code] = $entry['name'] ?? $code;
        }

        $tabs = [];
        $rows = NpcSellList::query()
            ->where('SellID', $base['sell_id'])
            ->get(['ItemType', 'ItemList']);

        foreach ($rows as $row) {
            $tabs[(int) $row->ItemType] = $this->parseCodes((string) $row->ItemList, $names);
        }

        $npc = Npc::query()->where('UniqueID', $base['id'])->first();
        $base['tabs'] = $tabs;
        $base['active'] = (int) ($npc?->Active ?? 1) === 1;
        $base['size'] = $npc?->Size;
        $base['level'] = $npc?->iLevel;
        $base['sound'] = $npc?->Sound;
        $base['code_type'] = $npc?->CodeType;
        $base['messages'] = $npc?->MessageIDs;
        $base['sell_types'] = config('valhalla.npc_sell_types');
        $base['item_count'] = array_sum(array_map('count', $tabs));

        return $base;
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    public function parseCodes(string $items, array $names = []): array
    {
        $tokens = preg_split('/\s+/', trim($items), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_map(function ($token) use ($names) {
            $code = strtoupper($token);

            return [
                'code' => $code,
                'name' => $names[$code] ?? $code,
            ];
        }, $tokens);
    }
}
