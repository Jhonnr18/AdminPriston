<?php

namespace App\Repositories;

use App\Models\GameServer\GameItem;
use App\Support\DemoCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class ItemRepository
{
    private ?array $healthCache = null;

    private ?Collection $allCodesCache = null;

    public function __construct(
        private readonly ValhallaDatabase $database,
        private readonly ItemsHRepository $itemsH,
        private readonly ClientAssetRepository $assets,
    ) {}

    /**
     * @return list<string>
     */
    public function tables(): array
    {
        return array_values(config('valhalla.item_tables'));
    }

    public function assertTable(string $table): string
    {
        if (! in_array($table, $this->tables(), true)) {
            throw new InvalidArgumentException("Tabela de item inválida: {$table}");
        }

        return $table;
    }

    /**
     * @return array<string, int>
     */
    public function tableCounts(): array
    {
        if ($this->database->usingFixtures()) {
            return DemoCatalog::itemTables();
        }

        return Cache::remember('valhalla.item_table_counts', 120, function () {
            $counts = [];
            foreach ($this->tables() as $table) {
                try {
                    $counts[$table] = GameItem::queryTable($table)->count();
                } catch (\Throwable) {
                    $counts[$table] = 0;
                }
            }

            return $counts;
        });
    }

    public function totalCount(): int
    {
        return array_sum($this->tableCounts());
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function listByTable(string $table, ?string $search = null, int $page = 1, int $perPage = 50): array
    {
        $table = $this->assertTable($table);
        $header = $this->itemsH->catalog();
        $page = max(1, $page);
        $perPage = max(10, min(100, $perPage));

        if ($this->database->usingFixtures()) {
            $all = array_map(
                fn (array $item) => $this->present($item['code'], $item['db'], $item['level'] ?? null, $item['spec'] ?? null, $header, $table),
                DemoCatalog::weaponFamily('WA')
            );
            $total = count($all);

            return [
                'items' => array_slice($all, ($page - 1) * $perPage, $perPage),
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ];
        }

        try {
            $query = GameItem::queryTable($table)->orderBy('Code');

            if (is_string($search) && $search !== '') {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('Code', 'like', $like)->orWhere('Name', 'like', $like);
                });
            }

            $total = (clone $query)->count();
            $items = $query->forPage($page, $perPage)->get()->map(function ($row) use ($header, $table) {
                $code = strtoupper((string) ($row->Code ?? $row->ID ?? ''));

                return $this->present(
                    $code,
                    (string) ($row->Name ?? ''),
                    $row->ItemLevel ?? null,
                    $row->PrimarySpec ?? $row->SecondarySpec ?? null,
                    $header,
                    $table,
                    (int) ($row->Active ?? 1) === 1,
                    $row->Price ?? null,
                );
            })->all();

            return [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ];
        } catch (\Throwable) {
            return [
                'items' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $perPage,
                'last_page' => 1,
            ];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function family(string $prefix): array
    {
        $prefix = strtoupper($prefix);
        $family = config('valhalla.families.'.$prefix);
        $table = is_array($family) ? ($family['table'] ?? 'Weapons') : 'Weapons';
        $table = $this->assertTable($table);
        $header = $this->itemsH->catalog();

        if ($this->database->usingFixtures()) {
            return array_map(
                fn (array $item) => $this->present($item['code'], $item['db'], $item['level'] ?? null, $item['spec'] ?? null, $header, $table),
                DemoCatalog::weaponFamily(str_starts_with($prefix, 'WV') ? 'WV' : 'WA')
            );
        }

        return GameItem::queryTable($table)
            ->where('Code', 'like', $prefix.'%')
            ->orderBy('Code')
            ->get()
            ->map(function ($row) use ($header, $table) {
                $code = strtoupper((string) ($row->Code ?? ''));

                return $this->present(
                    $code,
                    (string) ($row->Name ?? ''),
                    $row->ItemLevel ?? null,
                    $row->PrimarySpec ?? $row->SecondarySpec ?? null,
                    $header,
                    $table,
                    (int) ($row->Active ?? 1) === 1,
                    $row->Price ?? null,
                );
            })
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function allCodes(): Collection
    {
        if ($this->database->usingFixtures()) {
            return collect(DemoCatalog::weaponFamily('WA'))->map(fn ($item) => [
                'code' => $item['code'],
                'name' => $item['db'],
                'table' => 'Weapons',
            ]);
        }

        if ($this->allCodesCache) {
            return $this->allCodesCache;
        }

        $rows = collect();
        foreach ($this->tables() as $table) {
            try {
                $query = GameItem::queryTable($table);
                $rows = $rows->merge(
                    $query->get()
                        ->map(fn ($row) => [
                            'code' => strtoupper((string) ($row->Code ?? $row->ID ?? '')),
                            'name' => (string) ($row->Name ?? ''),
                            'table' => $table,
                        ])
                        ->filter(fn ($row) => $row['code'] !== '')
                );
            } catch (\Throwable) {
                continue;
            }
        }

        return $this->allCodesCache = $rows->values();
    }

    public function nameMap(): array
    {
        return $this->allCodes()->mapWithKeys(fn ($row) => [$row['code'] => $row['name']])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function problems(int $limit = 20): array
    {
        if ($this->database->usingFixtures()) {
            return DemoCatalog::problems();
        }

        return $this->catalogHealth($limit)['problems'];
    }

    /**
     * @return array{
     *     total: int,
     *     missing_header: int,
     *     missing_icon: int,
     *     missing_drop: int,
     *     repeated_hint: string,
     *     problems: list<array<string, mixed>>
     * }
     */
    public function catalogHealth(int $problemLimit = 20): array
    {
        if ($this->healthCache) {
            return $this->healthCache;
        }

        $codes = $this->allCodes();
        $header = $this->itemsH->catalog();
        $missingHeader = 0;
        $missingIcon = 0;
        $missingDrop = 0;
        $problems = [];

        foreach ($codes as $row) {
            $code = $row['code'];
            $reasons = [];

            if (! isset($header[$code])) {
                $missingHeader++;
                $reasons[] = 'sem items.h / CODE=0';
            }
            if (! $this->assets->iconExists($code)) {
                $missingIcon++;
                $reasons[] = 'sem ícone';
            }
            if (! $this->assets->dropMeshExists($code)) {
                $missingDrop++;
                $reasons[] = 'sem modelo de chão';
            }
            if (str_starts_with($code, 'WV') && (bool) config('valhalla.protect_wv') && count($problems) < $problemLimit) {
                $reasons[] = 'nome placeholder (protegido)';
            }

            if ($reasons !== [] && count($problems) < $problemLimit) {
                $problems[] = [
                    'code' => $code,
                    'name' => $row['name'],
                    'reason' => $reasons[0],
                    'kind' => $row['table'],
                ];
            }
        }

        $grouped = $codes
            ->filter(fn ($row) => str_starts_with($row['code'], 'WV'))
            ->groupBy('name')
            ->sortByDesc(fn (Collection $group) => $group->count())
            ->first();

        return $this->healthCache = [
            'total' => $codes->count(),
            'missing_header' => $missingHeader,
            'missing_icon' => $missingIcon,
            'missing_drop' => $missingDrop,
            'repeated_hint' => ($grouped && $grouped->count() >= 2) ? 'WV ×'.$grouped->count() : '—',
            'problems' => $problems,
        ];
    }

    public function missingHeaderCount(): int
    {
        return $this->catalogHealth()['missing_header'];
    }

    public function missingIconCount(): int
    {
        return $this->catalogHealth()['missing_icon'];
    }

    public function missingDropMeshCount(): int
    {
        return $this->catalogHealth()['missing_drop'];
    }

    public function repeatedNameHint(): string
    {
        return $this->catalogHealth()['repeated_hint'];
    }

    public function familyMeta(string $prefix): array
    {
        $prefix = strtoupper($prefix);
        $family = config('valhalla.families.'.$prefix, [
            'table' => 'Weapons',
            'label' => $prefix,
            'protected' => false,
        ]);

        return [
            'prefix' => $prefix,
            'table' => $family['table'] ?? 'Weapons',
            'label' => $family['label'] ?? $prefix,
            'protected' => (bool) ($family['protected'] ?? false) && (bool) config('valhalla.protect_wv'),
        ];
    }

    /**
     * Detalhe de um item por Code (para modal).
     *
     * @return array<string, mixed>|null
     */
    public function detail(string $code): ?array
    {
        $code = strtoupper(trim($code));
        if ($code === '' || in_array($code, ['GOLD', 'AIR'], true)) {
            return null;
        }

        $header = $this->itemsH->catalog();
        $guess = $this->guessTableForCode($code);

        if ($this->database->usingFixtures()) {
            $demo = collect(DemoCatalog::weaponFamily(str_starts_with($code, 'WV') ? 'WV' : 'WA'))
                ->firstWhere('code', $code);
            if (! $demo) {
                return null;
            }

            $base = $this->present($code, $demo['db'], $demo['level'] ?? null, $demo['spec'] ?? null, $header, $guess ?? 'Weapons');

            return $this->enrichDetail($base, [
                'ItemLevel' => $demo['level'] ?? null,
                'PrimarySpec' => $demo['spec'] ?? null,
                'Price' => null,
            ]);
        }

        $tables = $guess !== null
            ? array_values(array_unique([$guess, ...$this->tables()]))
            : $this->tables();

        foreach ($tables as $table) {
            try {
                $row = GameItem::queryTable($table)->where('Code', $code)->first();
            } catch (\Throwable) {
                continue;
            }
            if (! $row) {
                continue;
            }

            $attrs = $row->getAttributes();
            $base = $this->present(
                $code,
                (string) ($row->Name ?? ''),
                $row->ItemLevel ?? null,
                $row->PrimarySpec ?? $row->SecondarySpec ?? null,
                $header,
                $table,
                (int) ($row->Active ?? 1) === 1,
                $row->Price ?? null,
            );

            return $this->enrichDetail($base, $attrs);
        }

        if (isset($header[$code])) {
            $base = $this->present($code, $header[$code]['name'], null, null, $header, $guess ?? 'Weapons');

            return $this->enrichDetail($base, []);
        }

        return null;
    }

    public function guessTableForCode(string $code): ?string
    {
        $code = strtoupper($code);
        $families = config('valhalla.families', []);

        foreach (['DA2', substr($code, 0, 3), substr($code, 0, 2)] as $prefix) {
            if (isset($families[$prefix]['table'])) {
                return (string) $families[$prefix]['table'];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $attrs
     * @return array<string, mixed>
     */
    private function enrichDetail(array $base, array $attrs): array
    {
        $pick = static function (array $keys) use ($attrs): array {
            $out = [];
            foreach ($keys as $key) {
                if (array_key_exists($key, $attrs) && $attrs[$key] !== null && $attrs[$key] !== '') {
                    $out[$key] = $attrs[$key];
                }
            }

            return $out;
        };

        $base['folder'] = $this->itemsH->folderFor($base['code']);
        $base['missing_icon'] = ! $this->assets->iconExists($base['code']);
        $base['missing_drop'] = ! $this->assets->dropMeshExists($base['code']);
        $base['icon_src'] = url('/icon.php?c='.$base['code']);
        $base['requirements'] = $pick([
            'ItemLevel', 'ItemSpirit', 'ItemStrength', 'ItemTalent', 'itemAgility', 'Weight',
        ]);
        $base['combat'] = $pick([
            'AttackPowerMin1', 'AttackPowerMax1', 'AttackPowerMin2', 'AttackPowerMax2',
            'AttackRatingMin', 'AttackRatingMax', 'AttackCritical', 'AttackSpeed', 'AttackRange',
            'BlockMin', 'BlockMax', 'DurabilityMin', 'DurabilityMax',
            'Defense', 'Absorption', 'Organic', 'Fire', 'Frost', 'Lightning', 'Poison',
        ]);
        $base['spec_stats'] = $pick([
            'PrimarySpec', 'SecondarySpec',
            'SpecAttackPowerMin', 'SpecAttackPowerMax',
            'SpecAttackRatingMin', 'SpecAttackRatingMax',
            'SpecAttackSpeed', 'SpecAttackCritical', 'SpecAttackRange',
        ]);
        $base['extra'] = $pick(['ItemType', 'ItemEffect', 'UniqueItem', 'EffectBlink']);

        return $base;
    }

    /**
     * @param  array<string, array{code: string, name: string}>  $header
     * @return array<string, mixed>
     */
    private function present(
        string $code,
        string $dbName,
        mixed $level,
        mixed $spec,
        array $header,
        string $table,
        bool $active = true,
        mixed $price = null,
    ): array {
        $headerName = $header[$code]['name'] ?? null;

        return [
            'code' => $code,
            'db' => $dbName,
            'header' => $headerName ?? '—',
            'level' => $level,
            'spec' => $spec,
            'table' => $table,
            'active' => $active,
            'price' => $price,
            'protected' => str_starts_with($code, 'WV') && (bool) config('valhalla.protect_wv'),
            'missing_header' => $headerName === null,
            // Não consulta disco na listagem (OneDrive/lento). A <img> cai no placeholder se 404.
            'missing_icon' => false,
            'missing_drop' => false,
            'icon_url' => $this->assets->iconUrl($code),
        ];
    }
}
