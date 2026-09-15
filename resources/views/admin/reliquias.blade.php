@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    Config em <span class="mono">ReliquiaDef</span> / <span class="mono">ReliquiaBonus</span>. Após salvar: <span class="mono">/reload_relic</span>. Quem já tem a relíquia sente o número novo no recálculo (não é snapshot de item).
</div>

<div class="vlh-card">
    <table class="vlh-table">
        <thead><tr><th>Slot</th><th>Nome</th><th>Item</th><th>Bônus (demo)</th></tr></thead>
        <tbody>
            <tr><td>0</td><td>Foice do Babel</td><td class="mono">RR101</td><td>dano</td></tr>
            <tr><td>4</td><td>Capuz do Mokova</td><td class="mono">RR105</td><td>defesa + vida</td></tr>
            <tr><td>10</td><td>Asa do Midranda</td><td class="mono">RR111</td><td>resistências / evade</td></tr>
        </tbody>
    </table>
</div>
@endsection
