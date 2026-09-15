@extends('layouts.admin')

@section('actions')
<div class="vlh-actions m-0">
    <a class="vlh-btn {{ $prefix === 'WA' ? 'primary' : '' }}" href="{{ route('familia', ['prefix' => 'WA']) }}">WA</a>
    <a class="vlh-btn {{ $prefix === 'WV' ? 'primary' : '' }}" href="{{ route('familia', ['prefix' => 'WV']) }}">WV</a>
    <button type="button" class="vlh-btn" disabled title="Demo: liga no SQL">Trocar nomes</button>
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
    O código (<span class="mono">{{ $prefix }}110</span>) é a identidade. O nome é o que o jogador lê. O arquivo <span class="mono">it{{ $prefix }}110.bmp</span> é o que ele vê. Esta tela conserta nome vs ícone sem mudar o código, a menos que você copie o arquivo.
</div>

<p class="text-sm mb-3" style="color: var(--color-vlh-muted)">
    Família <span class="mono">{{ $prefix }}</span>
    · {{ $prefix === 'WV' ? 'Punho · spec 11 Marcial' : 'Machado · spec 1 Fighter' }}
</p>

<div class="vlh-family" id="family-track">
    @foreach ($items as $i => $item)
        <div class="vlh-tier" data-code="{{ $item['code'] }}" onclick="this.classList.toggle('selected')">
            <div class="vlh-icon {{ strtolower(substr($item['code'],0,2)) }} mb-2" style="width:48px;height:48px">{{ substr($item['code'],2) }}</div>
            <div class="mono text-xs" style="color: var(--color-vlh-gold)">{{ $item['code'] }}</div>
            <div class="text-xs mt-1">banco: {{ $item['db'] }}</div>
            <div class="text-xs" style="color: var(--color-vlh-muted)">header: {{ $item['header'] }}</div>
            <div class="text-xs mt-1" style="color: var(--color-vlh-muted)">Lv {{ $item['level'] }}</div>
        </div>
    @endforeach
</div>
@endsection
