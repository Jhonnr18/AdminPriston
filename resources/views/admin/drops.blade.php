@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    Clique no monstro para abrir o modal com stats e drops. Chance é <strong>peso relativo</strong> (não percentual 0–100). Tokens especiais: <span class="mono">Gold</span>, <span class="mono">Air</span>.
</div>

<div class="vlh-card">
    <table class="vlh-table">
        <thead>
            <tr><th>Monstro</th><th>Level</th><th>DropID</th><th>Linhas</th><th></th></tr>
        </thead>
        <tbody>
        @foreach ($monsters as $m)
            <tr class="vlh-open" data-vlh-modal="monster" data-vlh-id="{{ $m['name'] }}" role="button" tabindex="0">
                <td>
                    {{ $m['name'] }}
                    @if ($m['boss']) <span class="vlh-badge gold">boss</span> @endif
                </td>
                <td>{{ $m['level'] }}</td>
                <td class="mono">{{ $m['drop_id'] }}</td>
                <td>{{ $m['rows'] }}</td>
                <td>
                    <a class="vlh-btn" href="{{ route('drops.show', $m['name']) }}" onclick="event.stopPropagation()">Página</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
