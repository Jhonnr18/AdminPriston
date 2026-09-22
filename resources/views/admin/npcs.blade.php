@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    Clique no NPC para ver a loja completa no modal. O preço é do item, não do NPC. <span class="mono">NpcSellList</span> guarda códigos separados por espaço.
</div>

<div class="grid gap-4" style="grid-template-columns: 240px 1fr;">
    <div class="vlh-card" style="max-height:70vh;overflow:auto">
        <div class="text-xs mb-2" style="color: var(--color-vlh-muted)">NPCs</div>
        @forelse ($npcs as $npc)
            <button type="button"
               class="vlh-btn vlh-open w-full mb-1 {{ ($selected['id'] ?? null) == $npc['id'] ? 'primary' : '' }}"
               data-vlh-modal="npc"
               data-vlh-id="{{ $npc['id'] }}">
                {{ $npc['name'] }}
                @if (($npc['shared'] ?? 1) > 1)
                    <span class="vlh-badge warn">{{ $npc['shared'] }}</span>
                @endif
            </button>
        @empty
            <p class="text-sm m-0" style="color: var(--color-vlh-muted)">Nenhum NPC carregado.</p>
        @endforelse
    </div>
    <div class="vlh-card">
        @if ($selected)
            <div class="font-semibold mb-1">{{ $selected['name'] }}</div>
            <div class="text-xs mb-3" style="color: var(--color-vlh-muted)">
                SellID <span class="mono">{{ $selected['sell_id'] }}</span>
                @if (($selected['shared'] ?? 1) > 1)
                    · usada por {{ $selected['shared'] }} NPCs
                @endif
                · <button type="button" class="vlh-btn vlh-open" style="display:inline;padding:.15rem .45rem" data-vlh-modal="npc" data-vlh-id="{{ $selected['id'] }}">Abrir modal</button>
            </div>
            <div class="flex gap-2 mb-3">
                @foreach ($sellTypes as $id => $label)
                    <a href="{{ route('npcs', ['npc' => $selected['id'], 'tab' => $id]) }}"
                       class="vlh-btn {{ (int) $tab === (int) $id ? 'primary' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-2 min-h-16 p-3" style="border:1px dashed var(--color-vlh-border);border-radius:.4rem">
                @forelse (($selected['tabs'][$tab] ?? []) as $item)
                    <span class="vlh-chip vlh-open" data-vlh-modal="item" data-vlh-id="{{ $item['code'] }}" role="button" tabindex="0">
                        @include('components.item-icon', ['code' => $item['code'], 'size' => 22])
                        <span class="mono">{{ $item['code'] }}</span>
                        <span style="color: var(--color-vlh-muted)">{{ $item['name'] }}</span>
                    </span>
                @empty
                    <span style="color: var(--color-vlh-muted)">Nenhum item nesta aba.</span>
                @endforelse
            </div>
        @endif
    </div>
</div>
@endsection
