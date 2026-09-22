<?php

namespace App\Repositories;

class ClientAssetRepository
{
    /** @var array<string, ?string> */
    private array $iconPathCache = [];

    /** @var array<string, ?string> */
    private array $dropPathCache = [];

    public function __construct(
        private readonly ItemsHRepository $itemsH,
    ) {}

    public function iconExists(string $code): bool
    {
        return $this->iconPath($code) !== null;
    }

    public function dropMeshExists(string $code): bool
    {
        return $this->dropMeshPath($code) !== null;
    }

    public function iconUrl(string $code): ?string
    {
        $code = strtoupper(trim($code));
        if ($code === '' || in_array($code, ['GOLD', 'AIR'], true)) {
            return null;
        }

        // Sempre gera URL; a rota resolve no disco e cai no placeholder se faltar.
        return route('media.icon', ['code' => $code]);
    }

    public function iconPath(string $code): ?string
    {
        $code = strtoupper(trim($code));
        if ($code === '' || in_array($code, ['GOLD', 'AIR'], true)) {
            return null;
        }

        if (array_key_exists($code, $this->iconPathCache)) {
            return $this->iconPathCache[$code];
        }

        $filename = str_replace('{code}', $code, (string) config('valhalla.icon_filename_tpl'));
        $root = rtrim((string) config('valhalla.client_items_root'), '\\/');
        if ($root === '') {
            return $this->iconPathCache[$code] = null;
        }

        $folders = [];
        $fromHeader = $this->itemsH->folderFor($code);
        if (is_string($fromHeader) && $fromHeader !== '') {
            $folders[] = $fromHeader;
        }
        foreach ($this->foldersForPrefix($code) as $folder) {
            $folders[] = $folder;
        }
        foreach ((array) config('valhalla.icon_subfolders') as $folder) {
            $folders[] = $folder;
        }
        $folders[] = '';

        foreach (array_unique($folders) as $folder) {
            $path = $folder === ''
                ? $root.DIRECTORY_SEPARATOR.$filename
                : $root.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$filename;
            if (@is_file($path)) {
                return $this->iconPathCache[$code] = $path;
            }
        }

        return $this->iconPathCache[$code] = null;
    }

    public function dropMeshPath(string $code): ?string
    {
        $code = strtoupper(trim($code));
        if ($code === '' || in_array($code, ['GOLD', 'AIR'], true)) {
            return null;
        }

        if (array_key_exists($code, $this->dropPathCache)) {
            return $this->dropPathCache[$code];
        }

        $root = rtrim((string) config('valhalla.client_dropitem_root'), '\\/');
        if ($root === '') {
            return $this->dropPathCache[$code] = null;
        }

        foreach ([
            str_replace(['{dorp}', '{code}'], $code, (string) config('valhalla.drop_mesh_tpl')),
            'it'.$code.'.smd',
            'it'.$code.'.ASE',
            'it'.$code.'.ase',
        ] as $name) {
            $path = $root.DIRECTORY_SEPARATOR.$name;
            if (@is_file($path)) {
                return $this->dropPathCache[$code] = $path;
            }
        }

        return $this->dropPathCache[$code] = null;
    }

    /**
     * Mapa prefixo alfa (2 letras) → pasta(s) de asset do cliente. Público porque
     * ItemSkinRepository reaproveita esta mesma tabela pra validar compatibilidade
     * de categoria entre um item e a skin candidata (ex: recusar arma virando anel).
     *
     * @return list<string>
     */
    public function foldersForPrefix(string $code): array
    {
        $prefix = strtoupper(substr($code, 0, 2));

        return match ($prefix) {
            'WA', 'WC', 'WH', 'WP', 'WS', 'WT', 'WD', 'WN', 'WV', 'WM' => ['Weapon'],
            'DA', 'DS', 'DB', 'DG' => ['Defense'],
            'OA', 'OR', 'OB', 'OS', 'OE' => ['Accessory'],
            'CA' => ['Defense', 'Event'],
            'BI', 'BC', 'BD' => ['Premium'],
            'PL', 'PS', 'PM' => ['Potion'],
            'QT', 'QE' => ['Quest'],
            'GP', 'FO' => ['Accessory', 'Defense'],
            'RR' => ['Accessory', 'Defense', 'Premium'],
            'AS' => ['Wing'],
            default => [],
        };
    }
}
