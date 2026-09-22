<?php

namespace App\Repositories;

use App\Support\DemoCatalog;
use Illuminate\Support\Facades\Cache;

class OverviewRepository
{
    public function __construct(
        private readonly ValhallaDatabase $database,
        private readonly ItemRepository $items,
        private readonly DropRepository $drops,
        private readonly ItemsHRepository $itemsH,
    ) {}

    /**
     * Overview rápida: SQL + items.h. Varredura de BMP/.smd fica em cache
     * (preenchida sob demanda pela rota de mídia / comando futuro), para não
     * travar o request em pastas lentas (OneDrive).
     *
     * @return array{total: int, missing_header: int, repeated_hint: string, problems: list<array<string, mixed>>, monsters: int}
     */
    private function summary(): array
    {
        return Cache::remember('valhalla.overview_summary_fast', 120, function () {
            $codes = $this->items->allCodes();
            $header = $this->itemsH->catalog();
            $missingHeader = 0;
            $problems = [];

            foreach ($codes as $row) {
                $code = $row['code'];
                if (isset($header[$code])) {
                    continue;
                }

                $missingHeader++;
                if (count($problems) < 20) {
                    $problems[] = [
                        'code' => $code,
                        'name' => $row['name'],
                        'reason' => 'sem items.h / CODE=0',
                        'kind' => $row['table'],
                    ];
                }
            }

            $grouped = $codes
                ->filter(fn ($row) => str_starts_with($row['code'], 'WV'))
                ->groupBy('name')
                ->sortByDesc(fn ($group) => $group->count())
                ->first();

            return [
                'total' => $codes->count(),
                'missing_header' => $missingHeader,
                'repeated_hint' => ($grouped && $grouped->count() >= 2) ? 'WV ×'.$grouped->count() : '—',
                'problems' => $problems,
                'monsters' => $this->drops->monsterCount(),
            ];
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function kpis(): array
    {
        if ($this->database->demo()) {
            return DemoCatalog::kpis();
        }

        $summary = $this->summary();
        $missingIcon = Cache::get('valhalla.missing_icon_count');
        $missingDrop = Cache::get('valhalla.missing_drop_count');

        return [
            [
                'label' => 'Itens no banco',
                'value' => number_format($summary['total'], 0, ',', '.'),
                'hint' => '21 tabelas',
                'route' => 'itens',
            ],
            [
                'label' => 'Sem items.h',
                'value' => (string) $summary['missing_header'],
                'hint' => 'CODE=0',
                'route' => 'itens',
                'tone' => $summary['missing_header'] > 0 ? 'danger' : null,
            ],
            [
                'label' => 'Sem ícone',
                'value' => $missingIcon === null ? '…' : (string) $missingIcon,
                'hint' => $missingIcon === null ? 'sob demanda' : 'BMP ausente',
                'route' => 'itens',
                'tone' => ($missingIcon ?? 0) > 0 ? 'warn' : null,
            ],
            [
                'label' => 'Sem modelo de chão',
                'value' => $missingDrop === null ? '…' : (string) $missingDrop,
                'hint' => $missingDrop === null ? 'sob demanda' : 'DropItem',
                'route' => 'itens',
                'tone' => ($missingDrop ?? 0) > 0 ? 'warn' : null,
            ],
            [
                'label' => 'Nomes repetidos',
                'value' => $summary['repeated_hint'],
                'hint' => 'família WV',
                'route' => 'familia',
                'tone' => 'warn',
            ],
            [
                'label' => 'Monstros',
                'value' => (string) $summary['monsters'],
                'hint' => 'MonsterList',
                'route' => 'drops',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function problems(): array
    {
        if ($this->database->demo()) {
            return DemoCatalog::problems();
        }

        return $this->summary()['problems'];
    }
}
