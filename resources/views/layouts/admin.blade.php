<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Painel' }} · Valhalla Catálogo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
@php
    $navJogo = [
        ['route' => 'overview', 'label' => 'Visão geral'],
        ['route' => 'itens', 'label' => 'Itens'],
        ['route' => 'familia', 'label' => 'Linha da família'],
        ['route' => 'drops', 'label' => 'Drops dos monstros'],
        ['route' => 'skills', 'label' => 'Skills'],
        ['route' => 'pvp', 'label' => 'Dano em PvP'],
        ['route' => 'npcs', 'label' => 'NPCs e Lojas'],
        ['route' => 'coin-shop', 'label' => 'Loja de Coins'],
        ['route' => 'recompensas', 'label' => 'Recompensas'],
        ['route' => 'raridade', 'label' => 'Raridade'],
        ['route' => 'reliquias', 'label' => 'Relíquias'],
    ];
    $navAdmin = [
        ['route' => 'coins', 'label' => 'Coins e Time'],
        ['route' => 'servidor', 'label' => 'Servidor'],
    ];
    $current = request()->route()?->getName();
@endphp
<div class="vlh-shell">
    <aside class="vlh-side">
        <div class="vlh-brand">VALHALLA · CATÁLOGO</div>
        <nav class="vlh-nav">
            <div class="vlh-nav-group">Jogo</div>
            @foreach ($navJogo as $item)
                <a href="{{ route($item['route']) }}" class="{{ $current === $item['route'] || str_starts_with((string) $current, $item['route']) ? 'active' : '' }}">{{ $item['label'] }}</a>
            @endforeach
            <div class="vlh-nav-group">Administração</div>
            @foreach ($navAdmin as $item)
                <a href="{{ route($item['route']) }}" class="{{ $current === $item['route'] ? 'active' : '' }}">{{ $item['label'] }}</a>
            @endforeach
        </nav>
    </aside>
    <div>
        <header class="vlh-top">
            <div class="flex-1 max-w-xl">
                <input class="vlh-input" type="search" placeholder="Buscar código ou nome (ex. WA101)" disabled title="Liga quando o SQL estiver configurado">
            </div>
            <div class="text-right text-xs" style="color: var(--color-vlh-muted)">
                <div>SQL: {{ config('valhalla.sqlsrv.database') }} @ {{ config('valhalla.sqlsrv.host') }},{{ config('valhalla.sqlsrv.port') }}</div>
                <div class="mono">{{ config('valhalla.demo_mode') ? 'DEMO (fixtures)' : 'SQL Server' }} · Operador</div>
            </div>
        </header>
        <main class="vlh-main">
            <div class="flex items-end justify-between gap-4 mb-4">
                <div>
                    <h1 class="text-xl font-semibold m-0">{{ $pageTitle ?? 'Painel' }}</h1>
                    @isset($pageHint)
                        <p class="m-0 mt-1 text-sm" style="color: var(--color-vlh-muted)">{{ $pageHint }}</p>
                    @endisset
                </div>
                @yield('actions')
            </div>
            @yield('content')
        </main>
    </div>
</div>
@livewireScripts
</body>
</html>
