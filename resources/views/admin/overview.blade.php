@extends('layouts.admin')

@section('content')
<div class="grid gap-3 mb-4" style="grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));">
    @foreach ($kpis as $kpi)
        <a href="{{ route($kpi['route']) }}" class="vlh-kpi {{ isset($kpi['tone']) ? 'tone-'.$kpi['tone'] : '' }}" style="text-decoration:none;color:inherit">
            <div class="text-xs mb-1" style="color: var(--color-vlh-muted)">{{ $kpi['label'] }}</div>
            <div class="value">{{ $kpi['value'] }}</div>
            <div class="text-xs mt-1" style="color: var(--color-vlh-muted)">{{ $kpi['hint'] }}</div>
        </a>
    @endforeach
</div>

<div class="grid gap-4" style="grid-template-columns: 1.2fr 1fr;">
    <div class="vlh-card">
        <h2 class="text-sm font-semibold m-0 mb-3" style="color: var(--color-vlh-gold)">Fila de problemas</h2>
        <table class="vlh-table">
            <thead>
                <tr><th></th><th>Código</th><th>Nome</th><th>Motivo</th></tr>
            </thead>
            <tbody>
            @foreach ($problems as $p)
                <tr class="vlh-open" data-vlh-modal="item" data-vlh-id="{{ $p['code'] }}" role="button" tabindex="0">
                    <td>@include('components.item-icon', ['code' => $p['code'], 'size' => 40])</td>
                    <td class="mono">{{ $p['code'] }}</td>
                    <td>{{ $p['name'] }}</td>
                    <td><span class="vlh-badge danger">{{ $p['reason'] }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="vlh-card">
        <h2 class="text-sm font-semibold m-0 mb-3" style="color: var(--color-vlh-gold)">Últimas alterações</h2>
        <p class="text-sm m-0" style="color: var(--color-vlh-muted)">Auditoria em ValhallaAdmin — ainda vazia neste setup demo.</p>
        <div class="vlh-callout mt-3">
            O preço é do item, não do NPC. Listas de drop/loja usam códigos separados por <strong>espaço</strong>, não vírgula. Chance de drop é <strong>peso</strong>, não precisa somar 100.
        </div>
    </div>
</div>
@endsection
