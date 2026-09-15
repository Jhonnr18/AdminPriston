@extends('layouts.admin')

@section('actions')
<div class="vlh-actions m-0">
    <a class="vlh-btn primary" href="{{ route('familia', ['prefix' => 'WA']) }}">Abrir linha WA</a>
    <a class="vlh-btn" href="{{ route('familia', ['prefix' => 'WV']) }}">Linha WV (protegida)</a>
</div>
@endsection

@section('content')
<div class="vlh-callout mb-4">
    Identidade = <span class="mono">Code</span> (ex. WA101). O nome no jogo vem da coluna <span class="mono">Name</span> do SQL. O ícone segue <span class="mono">items.h</span> → <span class="mono">it&lt;LastCategory&gt;.bmp</span>.
</div>

<div class="flex flex-wrap gap-2 mb-4">
    @foreach ($tables as $name => $count)
        <a href="{{ route('itens', ['table' => $name]) }}"
           class="vlh-btn {{ $table === $name ? 'primary' : '' }}">
            {{ $name }} <span class="mono" style="opacity:.7">{{ $count }}</span>
        </a>
    @endforeach
</div>

<div class="vlh-card overflow-x-auto">
    <table class="vlh-table">
        <thead>
            <tr>
                <th></th><th>Code</th><th>Nome no jogo</th><th>Nome no cliente</th><th>Level</th><th>Spec</th><th></th>
            </tr>
        </thead>
        <tbody>
        @foreach ($items as $item)
            <tr>
                <td><div class="vlh-icon {{ strtolower(substr($item['code'],0,2)) }}">{{ substr($item['code'],0,2) }}</div></td>
                <td class="mono">{{ $item['code'] }}</td>
                <td>{{ $item['db'] }}</td>
                <td style="color: var(--color-vlh-muted)">{{ $item['header'] }}</td>
                <td>{{ $item['level'] }}</td>
                <td>{{ $item['spec'] }}</td>
                <td>
                    @if (!empty($item['protected']))
                        <span class="vlh-badge warn">protegido</span>
                    @endif
                    @if ($item['db'] !== $item['header'])
                        <span class="vlh-badge warn">nome ≠ header</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
