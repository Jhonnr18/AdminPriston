<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;

class ItemsHRepository
{
    /** @var array<string, array{code: string, name: string, folder: ?string}>|null */
    private ?array $catalogCache = null;

    /**
     * @return array<string, array{code: string, name: string, folder: ?string}>
     */
    public function catalog(?string $path = null): array
    {
        if ($this->catalogCache !== null && $path === null) {
            return $this->catalogCache;
        }

        $path ??= (string) config('valhalla.items_h_path');
        $cacheKey = 'valhalla.items_h.'.md5($path.'|'.(@filemtime($path) ?: 0));

        $parsed = Cache::remember($cacheKey, 600, function () use ($path) {
            return $this->parse(is_file($path) ? (string) file_get_contents($path) : '');
        });

        if ($path === (string) config('valhalla.items_h_path')) {
            $this->catalogCache = $parsed;
        }

        return $parsed;
    }

    /**
     * Parseia structs de items.h: { CODE, "Nome", "WA101", ..., "Weapon", ... }
     *
     * @return array<string, array{code: string, name: string, folder: ?string}>
     */
    public function parse(string $source): array
    {
        $source = preg_replace('#/\*.*?\*/#s', '', $source) ?? $source;
        $catalog = [];

        if (! preg_match_all(
            '/\{\s*[^"]*"([^"]+)"\s*,\s*"([A-Za-z]{2}\d{3})"\s*,[^"]*"([A-Za-z0-9_]+)"/',
            $source,
            $matches,
            PREG_SET_ORDER
        )) {
            return [];
        }

        foreach ($matches as $match) {
            $code = strtoupper($match[2]);
            $catalog[$code] = [
                'code' => $code,
                'name' => trim($match[1]),
                'folder' => $match[3] !== '' ? $match[3] : null,
            ];
        }

        return $catalog;
    }

    public function nameFor(string $code, ?array $catalog = null): ?string
    {
        $catalog ??= $this->catalog();

        return $catalog[strtoupper($code)]['name'] ?? null;
    }

    public function folderFor(string $code, ?array $catalog = null): ?string
    {
        $catalog ??= $this->catalog();

        return $catalog[strtoupper($code)]['folder'] ?? null;
    }
}
