@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    Chance é <strong>peso relativo</strong> (soma em <span class="mono">FallItemPerMax</span>), não percentual 0–100. Tokens especiais: <span class="mono">Gold</span>, <span class="mono">Air</span>. Vários monstros podem compartilhar o mesmo <span class="mono">DropID</span> — clone a lista antes de editar.
</div>

<div class="vlh-card">
    <table class="vlh-table">
        <thead>
            <tr><th>Monstro</th><th>Level</th><th>DropID</th><th>Linhas</th><th></th></tr>
        </thead>
        <tbody>
        @foreach ($monsters as $m)
            <tr>
                <td>
                    {{ $m['name'] }}
                    @if ($m['boss']) <span class="vlh-badge gold">boss</span> @endif
                </td>
                <td>{{ $m['level'] }}</td>
                <td class="mono">{{ $m['drop_id'] }}</td>
                <td>{{ $m['rows'] }}</td>
                <td><a class="vlh-btn" href="{{ route('drops.show', $m['name']) }}">Abrir</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
