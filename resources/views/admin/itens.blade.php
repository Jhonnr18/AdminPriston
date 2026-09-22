@extends('layouts.admin')

@section('actions')
<div class="vlh-actions m-0">
    <a class="vlh-btn primary" href="{{ route('familia', ['prefix' => 'WA']) }}">Abrir linha WA</a>
    <a class="vlh-btn" href="{{ route('familia', ['prefix' => 'WV']) }}">Linha WV (protegida)</a>
</div>
@endsection

@section('content')
<div class="vlh-callout mb-4">
    Clique numa linha para ver o detalhe. Identidade = <span class="mono">Code</span> (ex. WA101). O nome no jogo vem da coluna <span class="mono">Name</span> do SQL.
</div>

<div class="flex flex-wrap gap-2 mb-4">
    @foreach ($tables as $name => $count)
        <a href="{{ route('itens', ['table' => $name, 'q' => $search ?? null]) }}"
           class="vlh-btn {{ $table === $name ? 'primary' : '' }}">
            {{ $name }} <span class="mono" style="opacity:.7">{{ $count }}</span>
        </a>
    @endforeach
</div>

<p class="text-xs mb-3" style="color: var(--color-vlh-muted)">
    {{ $total }} itens · página {{ $page }}/{{ $lastPage }}
</p>

<div class="vlh-card overflow-x-auto">
    <table class="vlh-table">
        <thead>
            <tr>
                <th></th><th>Code</th><th>Nome no jogo</th><th>Nome no cliente</th><th>Level</th><th>Spec</th><th>Preço</th><th></th>
            </tr>
        </thead>
        <tbody>
        @foreach ($items as $item)
            <tr class="vlh-open" data-vlh-modal="item" data-vlh-id="{{ $item['code'] }}" role="button" tabindex="0">
                <td>@include('components.item-icon', ['code' => $item['code'], 'size' => 40])</td>
                <td class="mono">{{ $item['code'] }}</td>
                <td>{{ $item['db'] }}</td>
                <td style="color: var(--color-vlh-muted)">{{ $item['header'] }}</td>
                <td>{{ $item['level'] ?? '—' }}</td>
                <td>{{ $item['spec'] ?? '—' }}</td>
                <td>{{ $item['price'] ?? '—' }}</td>
                <td>
                    @if (!empty($item['protected']))
                        <span class="vlh-badge warn">protegido</span>
                    @endif
                    @if (!empty($item['missing_header']))
                        <span class="vlh-badge danger">sem items.h</span>
                    @elseif ($item['db'] !== $item['header'])
                        <span class="vlh-badge warn">nome ≠ header</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@if ($lastPage > 1)
<div class="vlh-actions mt-4">
    @if ($page > 1)
        <a class="vlh-btn" href="{{ route('itens', ['table' => $table, 'q' => $search, 'page' => $page - 1]) }}">Anterior</a>
    @endif
    @if ($page < $lastPage)
        <a class="vlh-btn primary" href="{{ route('itens', ['table' => $table, 'q' => $search, 'page' => $page + 1]) }}">Próxima</a>
    @endif
</div>
@endif
@endsection
