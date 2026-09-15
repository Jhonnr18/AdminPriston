@extends('layouts.admin')

@section('content')
<div class="vlh-callout danger mb-4">
    <strong>Não implementável só com SQL hoje.</strong> O servidor usa escala flat
    <span class="mono">kLegacyPvpDamageScalePercent = 20</span>
    (<span class="mono">CombatStrikePolicy.h</span>). Não há tabelas de multiplicador por habilidade ou atributo.
    Cliente ainda prediz defesa/absorção/bloco — alterar só no servidor gera desync visual.
</div>

<div class="vlh-card">
    <div class="text-sm mb-2">Estado atual (somente leitura)</div>
    <div class="mono text-2xl" style="color: var(--color-vlh-gold)">20%</div>
    <p class="text-sm mt-2 m-0" style="color: var(--color-vlh-muted)">
        Equivalente legado a Power / PK_POWER_DIVIDE. A UI do protótipo (% por skill / crítico / defesa / absorção / esquiva / bloqueio) fica bloqueada até o patch C++ + tabelas novas.
    </p>
</div>
@endsection
