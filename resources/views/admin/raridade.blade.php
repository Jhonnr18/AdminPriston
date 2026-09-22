@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    <strong>Chance</strong> já é SQL (<span class="mono">RarityChance*</span>, peso / {{ number_format($denominator ?? 10000000, 0, ',', '.') }}; Common = resto).
    <strong>Bônus</strong> (+HP, def, dano…) ainda estão em <span class="mono">ItemRarityBoost.h</span> — editar no painel exige patch.
</div>

<div class="flex gap-2 mb-4">
    <button class="vlh-btn primary" type="button">Chance</button>
    <button class="vlh-btn" disabled title="Precisa patch RarityBonus">Bônus por tipo</button>
</div>

<div class="vlh-card mb-4">
    <div class="text-sm font-semibold mb-2">Grupos de level (chance)</div>
    <table class="vlh-table">
        <thead><tr><th>Grupo</th><th>Min</th><th>Max</th><th>Common</th><th>Unc</th><th>Rare</th><th>Epic</th><th>Leg</th></tr></thead>
        <tbody>
        @forelse ($groups as $group)
            <tr>
                <td class="mono">{{ $group['id'] }}</td>
                <td>{{ $group['min'] }}</td>
                <td>{{ $group['max'] }}</td>
                <td class="mono">{{ $group['common'] }}</td>
                <td class="mono">{{ $group['uncommon'] }}</td>
                <td class="mono">{{ $group['rare'] }}</td>
                <td class="mono">{{ $group['epic'] }}</td>
                <td class="mono">{{ $group['legendary'] }}</td>
            </tr>
            @if (!empty($group['overflow']))
                <tr><td colspan="8"><span class="vlh-badge danger">soma acima de {{ $denominator }}</span></td></tr>
            @endif
        @empty
            <tr><td colspan="8" style="color: var(--color-vlh-muted)">Sem grupos no banco (ou modo demo).</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if (!empty($modifiers))
<div class="vlh-card mb-4">
    <div class="text-sm font-semibold mb-2">Modificadores (boss / etc.)</div>
    <table class="vlh-table">
        <thead><tr><th>Type</th><th>Common</th><th>Unc</th><th>Rare</th><th>Epic</th><th>Leg</th></tr></thead>
        <tbody>
        @foreach ($modifiers as $mod)
            <tr>
                <td class="mono">{{ $mod['type'] }}</td>
                <td>{{ $mod['common'] }}</td>
                <td>{{ $mod['uncommon'] }}</td>
                <td>{{ $mod['rare'] }}</td>
                <td>{{ $mod['epic'] }}</td>
                <td>{{ $mod['legendary'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="vlh-callout warn mb-4">
    Common não tem linha em <span class="mono">RarityChance</span> — o painel calcula o resto. Bônus de stats ficam bloqueados até existir <span class="mono">RarityBonus</span> no servidor.
</div>

@php
    $oldRow = old('_row');
@endphp

@if (!empty($groups))
<div class="vlh-card mb-4">
    <div class="text-sm font-semibold mb-2">Editar grupo de level</div>
    <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr))">
        @foreach ($groups as $group)
            @php $isOld = $oldRow === 'group-'.$group['id']; @endphp
            <form class="vlh-card" method="post" action="{{ route('raridade.grupo.update', $group['id']) }}">
                @csrf
                <input type="hidden" name="_row" value="group-{{ $group['id'] }}">
                <div class="text-xs mb-2" style="color: var(--color-vlh-muted)">Grupo <span class="mono">{{ $group['id'] }}</span> · Common (calculado): <span class="mono">{{ $group['common'] }}</span></div>

                <label class="text-xs" style="color: var(--color-vlh-muted)">Min level</label>
                <input class="vlh-input mb-2" type="number" name="min" value="{{ $isOld ? old('min') : $group['min'] }}">

                <label class="text-xs" style="color: var(--color-vlh-muted)">Max level</label>
                <input class="vlh-input mb-2" type="number" name="max" value="{{ $isOld ? old('max') : $group['max'] }}">

                <label class="text-xs" style="color: var(--color-vlh-muted)">Uncommon</label>
                <input class="vlh-input mb-2" type="number" min="0" name="uncommon" value="{{ $isOld ? old('uncommon') : $group['uncommon'] }}">

                <label class="text-xs" style="color: var(--color-vlh-muted)">Rare</label>
                <input class="vlh-input mb-2" type="number" min="0" name="rare" value="{{ $isOld ? old('rare') : $group['rare'] }}">

                <label class="text-xs" style="color: var(--color-vlh-muted)">Epic</label>
                <input class="vlh-input mb-2" type="number" min="0" name="epic" value="{{ $isOld ? old('epic') : $group['epic'] }}">

                <label class="text-xs" style="color: var(--color-vlh-muted)">Legendary</label>
                <input class="vlh-input mb-3" type="number" min="0" name="legendary" value="{{ $isOld ? old('legendary') : $group['legendary'] }}">

                @if ($isOld && $errors->has('raridade'))
                    <p class="text-sm mb-2" style="color: var(--color-vlh-danger, #f87171)">{{ $errors->first('raridade') }}</p>
                @endif

                <button class="vlh-btn primary" type="submit">Salvar grupo</button>
            </form>
        @endforeach
    </div>
</div>
@endif

@if (!empty($modifiers))
<div class="vlh-card mb-4">
    <div class="text-sm font-semibold mb-2">Editar modificador (boss / etc.)</div>
    <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr))">
        @foreach ($modifiers as $mod)
            @php $isOldMod = $oldRow === 'mod-'.$mod['type']; @endphp
            <form class="vlh-card" method="post" action="{{ route('raridade.mod.update', $mod['type']) }}">
                @csrf
                <input type="hidden" name="_row" value="mod-{{ $mod['type'] }}">
                <div class="text-xs mb-2" style="color: var(--color-vlh-muted)">Type <span class="mono">{{ $mod['type'] }}</span></div>

                <label class="text-xs" style="color: var(--color-vlh-muted)">Mod Common</label>
                <input class="vlh-input mb-2" type="number" step="any" name="common" value="{{ $isOldMod ? old('common') : $mod['common'] }}">

                <label class="text-xs" style="color: var(--color-vlh-muted)">Mod Uncommon</label>
                <input class="vlh-input mb-2" type="number" step="any" name="uncommon" value="{{ $isOldMod ? old('uncommon') : $mod['uncommon'] }}">

                <label class="text-xs" style="color: var(--color-vlh-muted)">Mod Rare</label>
                <input class="vlh-input mb-2" type="number" step="any" name="rare" value="{{ $isOldMod ? old('rare') : $mod['rare'] }}">

                <label class="text-xs" style="color: var(--color-vlh-muted)">Mod Epic</label>
                <input class="vlh-input mb-2" type="number" step="any" name="epic" value="{{ $isOldMod ? old('epic') : $mod['epic'] }}">

                <label class="text-xs" style="color: var(--color-vlh-muted)">Mod Legendary</label>
                <input class="vlh-input mb-3" type="number" step="any" name="legendary" value="{{ $isOldMod ? old('legendary') : $mod['legendary'] }}">

                @if ($isOldMod && $errors->has('raridade'))
                    <p class="text-sm mb-2" style="color: var(--color-vlh-danger, #f87171)">{{ $errors->first('raridade') }}</p>
                @endif

                <button class="vlh-btn primary" type="submit">Salvar modificador</button>
            </form>
        @endforeach
    </div>
</div>
@endif
@endsection
