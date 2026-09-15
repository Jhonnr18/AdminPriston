@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    O preço é do item, não do NPC. <span class="mono">NpcSellList</span> só guarda códigos (<span class="mono">sellType</span> 1=ataque, 2=defesa, 3=diversos) separados por espaço. Mudar <span class="mono">Weapons.Price</span> de WA101 altera todas as lojas que vendem WA101.
</div>
<div class="vlh-callout warn mb-4">
    Revenda do jogador para o NPC: não é 25% simples. Cliente calcula <span class="mono">PureSellPrice / 5</span> após ajuste de durabilidade (<span class="mono">sinInvenTory.cpp</span>).
</div>

<div class="grid gap-4" style="grid-template-columns: 220px 1fr;">
    <div class="vlh-card">
        <div class="text-xs mb-2" style="color: var(--color-vlh-muted)">Paleta</div>
        @foreach (['WA108','DA110','OS101','DB105'] as $code)
            <div class="flex items-center gap-2 mb-2">
                <div class="vlh-icon {{ strtolower(substr($code,0,2)) }}">{{ substr($code,0,2) }}</div>
                <span class="mono text-sm">{{ $code }}</span>
            </div>
        @endforeach
        <p class="text-xs m-0 mt-2" style="color: var(--color-vlh-muted)">Arrastar → aba (demo visual; gravação no SQL na próxima etapa)</p>
    </div>
    <div class="vlh-card">
        <div class="font-semibold mb-3">NPC demo · SellID 3</div>
        <div class="flex gap-2 mb-3">
            <button class="vlh-btn primary">Ataque</button>
            <button class="vlh-btn">Defesa</button>
            <button class="vlh-btn">Diversos</button>
        </div>
        <div class="flex flex-wrap gap-2 min-h-16 p-3" style="border:1px dashed var(--color-vlh-border);border-radius:.4rem">
            <span class="vlh-chip"><span class="mono">WA108</span></span>
            <span class="vlh-chip"><span class="mono">WA110</span></span>
        </div>
    </div>
</div>
@endsection
