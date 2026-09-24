<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Painel' }} · AdminPriston</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('vlh-modal.css') }}">
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
        ['route' => 'publicacoes', 'label' => 'Publicações e reloads'],
    ];
    $current = request()->route()?->getName();
@endphp
<div class="vlh-shell">
    <aside class="vlh-side">
        <div class="vlh-brand">AdminPriston</div>
        <div class="px-4 py-3 text-xs" style="color: var(--color-vlh-muted)">
            {{ auth()->user()->name }} · {{ auth()->user()->role }}
            <form method="post" action="{{ route('logout') }}" class="mt-2">@csrf<button type="submit">Sair</button></form>
        </div>
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
                <form method="get" action="{{ route('itens') }}">
                    <input class="vlh-input" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar código ou nome (ex. WA101)">
                </form>
            </div>
            <div class="text-right text-xs" style="color: var(--color-vlh-muted)">
                <div>SQL: {{ config('valhalla.sqlsrv.database') }} @ {{ config('valhalla.sqlsrv.host') }},{{ config('valhalla.sqlsrv.port') }}</div>
                <div class="mono">{{ $sqlStatus ?? '—' }} · {{ auth()->user()->name }}</div>
            </div>
        </header>
        <main class="vlh-main">
            @if (!empty($sqlWarning))
                <div class="vlh-callout danger mb-4">{{ $sqlWarning }}</div>
            @endif
            @if (!empty($sqlDemo))
                <div class="vlh-callout warn mb-4">Modo demo ativo (`VALHALLA_DEMO_MODE=true`). Os números abaixo são fixtures, não o Docker.</div>
            @endif
            @if (session('status'))
                <div class="vlh-callout mb-4">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="vlh-callout danger mb-4">{{ $errors->first() }}</div>
            @endif
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
