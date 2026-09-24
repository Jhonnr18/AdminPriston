@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    Diagnóstico somente leitura do ClanDB. Escritas, baú e custódia ficam
    bloqueados até as migrations e o journal serem homologados.
</div>

@if (!$online)
    <div class="vlh-callout warn mb-4">ClanDB offline ou modo demo.</div>
@endif

@foreach ($warnings as $warning)
    <div class="vlh-callout warn mb-2">{{ $warning }}</div>
@endforeach

<div class="vlh-card mb-4">
    <h3 class="font-semibold mb-3">Tabelas e contagens</h3>
    <table class="vlh-table w-full">
        <thead><tr><th>Tabela</th><th>Existente</th><th>Linhas</th></tr></thead>
        <tbody>
        @foreach ($tables as $name => $table)
            <tr><td class="mono">{{ $name }}</td><td>{{ $table['exists'] ? 'sim' : 'não' }}</td><td>{{ $table['rows'] ?? '—' }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="vlh-card">
    <h3 class="font-semibold mb-3">Migrations esperadas</h3>
    <ul class="text-sm">
        @foreach ($migrations as $migration)
            <li class="mb-1"><span class="mono">{{ $migration['name'] }}</span> — <span class="vlh-badge {{ $migration['status'] === 'applied' ? 'gold' : '' }}">{{ $migration['status'] }}</span></li>
        @endforeach
    </ul>
</div>

<div class="vlh-card mt-4">
    <h3 class="font-semibold mb-3">PKs, FKs e unicidade</h3>
    <table class="vlh-table w-full">
        <thead><tr><th>Nome</th><th>Status</th></tr></thead>
        <tbody>
        @foreach ($constraints as $constraint)
            <tr><td class="mono">{{ $constraint['name'] }}</td><td>{{ $constraint['exists'] ? 'presente' : 'ausente' }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
