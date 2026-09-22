@extends('layouts.admin')

@section('actions')
<div class="vlh-actions m-0">
        @foreach (($families ?? []) as $code => $family)
        <a class="vlh-btn {{ $prefix === $code ? 'primary' : '' }}" href="{{ route('familia', ['prefix' => $code]) }}">{{ $code }}</a>
    @endforeach
    <button type="button" class="vlh-btn" disabled title="Escrita na próxima etapa">Trocar nomes</button>
    <button type="button" class="vlh-btn" disabled>Aplicar nomes do items.h</button>
</div>
@endsection

@section('content')
@if ($prefix === 'WV' && $protectWv)
    <div class="vlh-callout warn mb-4">
        Família <span class="mono">WV*</span> (Artista Marcial) protegida por padrão. Stats/código/imagem travados — só renomear com permissão explícita (<span class="mono">catalog.override_wv</span>).
    </div>
@endif

<div class="vlh-callout mb-4">
    Clique num tier para ver o detalhe do item. O código é a identidade; o nome é o que o jogador lê.
</div>

<p class="text-sm mb-3" style="color: var(--color-vlh-muted)">
    Família <span class="mono">{{ $prefix }}</span>
    · {{ $meta['label'] ?? $prefix }}
    · tabela <span class="mono">{{ $meta['table'] ?? 'Weapons' }}</span>
</p>

<div class="vlh-family" id="family-track">
    @foreach ($items as $i => $item)
        <div class="vlh-tier vlh-open" data-vlh-modal="item" data-vlh-id="{{ $item['code'] }}" role="button" tabindex="0">
            @include('components.item-icon', ['code' => $item['code'], 'size' => 48, 'label' => substr($item['code'], 2)])
            <div class="mono text-xs" style="color: var(--color-vlh-gold)">{{ $item['code'] }}</div>
            <div class="text-xs mt-1">banco: {{ $item['db'] }}</div>
            <div class="text-xs" style="color: var(--color-vlh-muted)">header: {{ $item['header'] }}</div>
            <div class="text-xs mt-1" style="color: var(--color-vlh-muted)">Lv {{ $item['level'] }}</div>
        </div>
    @endforeach
</div>
@endsection
