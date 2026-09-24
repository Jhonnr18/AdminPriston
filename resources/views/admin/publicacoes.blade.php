@extends('layouts.admin')

@section('content')
    @if ($publicationError)
        <div class="vlh-callout warn mb-4">{{ $publicationError }}</div>
    @else
        <div class="vlh-card">
            <p class="text-sm mb-4">Fila de reload do PainelDB. A publicação só é concluída quando o servidor C++ atualizar o status.</p>
            <div class="overflow-auto">
                <table class="vlh-table w-full">
                    <thead><tr><th>Resource</th><th>Versão</th><th>Operador</th><th>Status</th><th>Solicitado em</th></tr></thead>
                    <tbody>
                    @forelse ($reloads as $reload)
                        <tr><td class="mono">{{ $reload['Resource'] ?? '—' }}</td><td>{{ $reload['VersionID'] ?? '—' }}</td><td>{{ $reload['RequestedBy'] ?? '—' }}</td><td>{{ $reload['Status'] ?? '—' }}</td><td>{{ $reload['RequestedAt'] ?? '—' }}</td></tr>
                    @empty
                        <tr><td colspan="5">Nenhum reload registrado.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
