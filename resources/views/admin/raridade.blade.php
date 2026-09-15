@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    <strong>Chance</strong> já é SQL (<span class="mono">RarityChance*</span>, peso / 10.000.000; Common = resto).
    <strong>Bônus</strong> (+HP, def, dano…) ainda estão em <span class="mono">ItemRarityBoost.h</span> — editar no painel exige patch.
</div>

<div class="flex gap-2 mb-4">
    <button class="vlh-btn primary">Chance</button>
    <button class="vlh-btn" disabled title="Precisa patch RarityBonus">Bônus por tipo</button>
</div>

<div class="vlh-card mb-4">
    <div class="text-sm font-semibold mb-2">Grupos de level (chance)</div>
    <table class="vlh-table">
        <thead><tr><th>Grupo</th><th>Min</th><th>Max</th><th>Unc</th><th>Rare</th><th>Epic</th><th>Leg</th></tr></thead>
        <tbody>
            <tr><td class="mono">1</td><td>0</td><td>102</td><td>—</td><td>—</td><td>—</td><td>—</td></tr>
            <tr><td colspan="7" style="color: var(--color-vlh-muted)">Valores reais após conectar o SQL e rodar docs/painel-admin/analise/sql/03-rarity-relics.sql</td></tr>
        </tbody>
    </table>
</div>

<div class="vlh-callout warn">
    Sugestão futura: bônus por <strong>tipo de item</strong> e dois grupos
    <span class="mono">ItemLevel ≤ 99</span> / <span class="mono">≥ 100</span>
    — ver <span class="mono">docs/painel-admin/40-sugestao-config-bonus-banco.md</span>.
</div>
@endsection
