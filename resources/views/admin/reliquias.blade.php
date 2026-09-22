@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    Config em <span class="mono">ReliquiaDef</span> / <span class="mono">ReliquiaBonus</span>. Após salvar: <span class="mono">/reload_relic</span> (o painel também enfileira o pedido em <span class="mono">ConfigReloadRequest</span> automaticamente). Slot {{ $lockedSlot ?? 11 }} permanece bloqueado. Quem já tem a relíquia sente o número novo no recálculo (não é snapshot de item).
</div>

<div class="vlh-card mb-4">
    <table class="vlh-table">
        <thead><tr><th>Slot</th><th>Nome</th><th>Item</th><th>Estado</th><th>Bônus</th></tr></thead>
        <tbody>
        @forelse ($relics as $relic)
            <tr>
                <td class="mono">{{ $relic['slot'] }}</td>
                <td>{{ $relic['name'] ?: '—' }}</td>
                <td class="mono">{{ $relic['item'] ?: '—' }}</td>
                <td>
                    @if (!empty($relic['locked']))
                        <span class="vlh-badge danger">bloqueado</span>
                    @elseif (!empty($relic['enabled']))
                        <span class="vlh-badge gold">ativa</span>
                    @else
                        <span class="vlh-badge">off</span>
                    @endif
                </td>
                <td>{{ implode(' · ', $relic['bonuses']) ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="color: var(--color-vlh-muted)">Nenhuma relíquia no banco.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@php
    $oldRow = old('_row');
    $bonusTypeOptions = collect(config('valhalla.relic_bonus_types'))->except(0);
@endphp

<div class="vlh-card mb-4">
    <div class="text-sm font-semibold mb-2">Editar relíquia (dados + item)</div>
    <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr))">
        @foreach ($relics as $relic)
            @if (!empty($relic['locked']))
                <div class="vlh-card">
                    <div class="text-xs mb-2" style="color: var(--color-vlh-muted)">Slot <span class="mono">{{ $relic['slot'] }}</span></div>
                    <p class="text-sm mb-0">Slot reservado/bloqueado pelo contrato do servidor — sem edição por aqui.</p>
                </div>
                @continue
            @endif
            @php $isOldDef = $oldRow === 'def-'.$relic['slot']; @endphp
            <form class="vlh-card" method="post" action="{{ route('reliquias.def.update', $relic['slot']) }}">
                @csrf
                <input type="hidden" name="_row" value="def-{{ $relic['slot'] }}">
                <div class="text-xs mb-2" style="color: var(--color-vlh-muted)">Slot <span class="mono">{{ $relic['slot'] }}</span></div>

                <label class="text-xs" style="color: var(--color-vlh-muted)">Nome (até 63 bytes UTF-8)</label>
                <input class="vlh-input mb-2" type="text" maxlength="64" name="name" value="{{ $isOldDef ? old('name') : $relic['name'] }}">

                <label class="text-xs" style="color: var(--color-vlh-muted)">Código do item</label>
                <input class="vlh-input mb-2" type="text" maxlength="10" name="item_code" value="{{ $isOldDef ? old('item_code') : $relic['item'] }}">

                <label class="text-xs" style="color: var(--color-vlh-muted)">Código do item de remoção (opcional)</label>
                <input class="vlh-input mb-2" type="text" maxlength="10" name="remover_code" value="{{ $isOldDef ? old('remover_code') : $relic['remover'] }}">

                <label class="flex items-center gap-2 text-sm mb-3">
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" name="enabled" value="1" {{ ($isOldDef ? old('enabled') : $relic['enabled']) ? 'checked' : '' }}>
                    Ativa
                </label>

                @if ($isOldDef && $errors->has('relic_def'))
                    <p class="text-sm mb-2" style="color: var(--color-vlh-danger, #f87171)">{{ $errors->first('relic_def') }}</p>
                @endif

                <button class="vlh-btn primary" type="submit">Salvar relíquia</button>
            </form>
        @endforeach
    </div>
</div>

<div class="vlh-card mb-4">
    <div class="text-sm font-semibold mb-2">Editar bônus da relíquia</div>
    <p class="text-xs mb-3" style="color: var(--color-vlh-muted)">
        Salvar substitui TODOS os bônus do slot pelo que estiver na lista abaixo. Tipo <span class="mono">0 (None)</span> não é selecionável. Valor precisa ser finito e maior que zero — o servidor descarta zero/negativo em silêncio. Tipo 37 (Evasão) tem teto de <span class="mono">10.0</span>. Limite global: 128 linhas somando todas as relíquias.
    </p>
    <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(22rem, 1fr))">
        @foreach ($relics as $relic)
            @continue(!empty($relic['locked']))
            @php
                $isOldBonus = $oldRow === 'bonus-'.$relic['slot'];
                $rows = $isOldBonus ? (old('bonuses') ?: []) : $relic['bonus_rows'];
            @endphp
            <form class="vlh-card" method="post" action="{{ route('reliquias.bonus.update', $relic['slot']) }}" data-relic-bonus-form>
                @csrf
                <input type="hidden" name="_row" value="bonus-{{ $relic['slot'] }}">
                <div class="text-xs mb-2" style="color: var(--color-vlh-muted)">Slot <span class="mono">{{ $relic['slot'] }}</span> — bônus</div>

                <div data-bonus-rows data-next-index="{{ count($rows) }}">
                    @forelse ($rows as $i => $row)
                        <div class="flex gap-2 mb-2" data-bonus-row>
                            <select class="vlh-input" name="bonuses[{{ $i }}][type]">
                                @foreach ($bonusTypeOptions as $typeId => $label)
                                    <option value="{{ $typeId }}" {{ (int) ($row['type'] ?? 0) === $typeId ? 'selected' : '' }}>{{ $typeId }} - {{ $label }}</option>
                                @endforeach
                            </select>
                            <input class="vlh-input" type="number" step="0.01" min="0.01" name="bonuses[{{ $i }}][value]" value="{{ $row['value'] ?? '' }}" placeholder="valor">
                            <button type="button" class="vlh-btn" data-remove-bonus-row title="remover">×</button>
                        </div>
                    @empty
                        <p class="text-sm mb-2" style="color: var(--color-vlh-muted)" data-bonus-empty-hint>Sem bônus configurado neste slot.</p>
                    @endforelse
                </div>

                <template data-bonus-row-template>
                    <div class="flex gap-2 mb-2" data-bonus-row>
                        <select class="vlh-input" name="bonuses[__INDEX__][type]">
                            @foreach ($bonusTypeOptions as $typeId => $label)
                                <option value="{{ $typeId }}">{{ $typeId }} - {{ $label }}</option>
                            @endforeach
                        </select>
                        <input class="vlh-input" type="number" step="0.01" min="0.01" name="bonuses[__INDEX__][value]" placeholder="valor">
                        <button type="button" class="vlh-btn" data-remove-bonus-row title="remover">×</button>
                    </div>
                </template>

                <button type="button" class="vlh-btn mb-3" data-add-bonus-row>+ mais um bônus</button>

                @if ($isOldBonus && $errors->has('relic_bonus'))
                    <p class="text-sm mb-2" style="color: var(--color-vlh-danger, #f87171)">{{ $errors->first('relic_bonus') }}</p>
                @endif

                <div><button class="vlh-btn primary" type="submit">Salvar bônus</button></div>
            </form>
        @endforeach
    </div>
</div>

<script>
document.addEventListener('click', function (e) {
    var addBtn = e.target.closest('[data-add-bonus-row]');
    if (addBtn) {
        e.preventDefault();
        var form = addBtn.closest('form');
        var rows = form.querySelector('[data-bonus-rows]');
        var template = form.querySelector('template[data-bonus-row-template]');
        var hint = rows.querySelector('[data-bonus-empty-hint]');
        if (hint) {
            hint.remove();
        }
        var index = parseInt(rows.getAttribute('data-next-index'), 10) || 0;
        rows.setAttribute('data-next-index', String(index + 1));
        var fragment = template.content.cloneNode(true);
        fragment.querySelectorAll('[name]').forEach(function (el) {
            el.name = el.name.replace('__INDEX__', String(index));
        });
        rows.appendChild(fragment);
        return;
    }

    var removeBtn = e.target.closest('[data-remove-bonus-row]');
    if (removeBtn) {
        e.preventDefault();
        removeBtn.closest('[data-bonus-row]').remove();
    }
});
</script>
@endsection
