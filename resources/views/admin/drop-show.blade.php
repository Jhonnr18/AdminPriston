@extends('layouts.admin')

@section('actions')
<div class="vlh-actions m-0">
    <button class="vlh-btn primary" disabled>Salvar</button>
    <button class="vlh-btn" disabled>Preview SQL</button>
    <a class="vlh-btn" href="{{ route('drops') }}">Voltar</a>
</div>
@endsection

@section('content')
<div class="vlh-card mb-4">
    <div class="text-lg font-semibold">{{ $meta['name'] }} · Lv {{ $meta['level'] }}</div>
    <div class="text-sm mt-1" style="color: var(--color-vlh-muted)">
        DropID <span class="mono">{{ $meta['drop_id'] }}</span>
        · máx. itens no chão: 3 · drop público: não
    </div>
</div>

@php $sum = collect($rows)->sum('chance'); @endphp
<div class="vlh-callout mb-4">
    Soma dos pesos: <span class="mono">{{ $sum }}</span>. Não precisa ser 100 — o servidor usa peso relativo.
</div>

<div class="space-y-3">
@foreach ($rows as $row)
    <div class="vlh-card">
        <div class="flex items-center justify-between gap-3 mb-2">
            <label class="text-xs" style="color: var(--color-vlh-muted)">Chance (peso)</label>
            <input class="vlh-input" style="max-width:7rem" type="number" value="{{ $row['chance'] }}" disabled>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach ($row['items'] as $it)
                <span class="vlh-chip">
                    <span class="vlh-icon {{ strtolower(substr($it['code'],0,2)) }}" style="width:22px;height:22px;font-size:0.55rem">{{ substr($it['code'],0,2) }}</span>
                    <span class="mono">{{ $it['code'] }}</span>
                    <span style="color: var(--color-vlh-muted)">{{ $it['name'] }}</span>
                    @isset($it['gold_min'])
                        <span>{{ $it['gold_min'] }}–{{ $it['gold_max'] }}</span>
                    @endisset
                </span>
            @endforeach
            <button class="vlh-btn" disabled>+ item</button>
        </div>
    </div>
@endforeach
</div>
@endsection
