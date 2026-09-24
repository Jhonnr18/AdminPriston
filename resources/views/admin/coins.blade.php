@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    Dar/tirar coins grava <span class="mono">UserDB.Users.UserCoin</span> na hora (sem cache). Toda operação vai para auditoria local <span class="mono">valhalla_audits</span> (SQLite do painel — não GameServer). Débito recusa saldo insuficiente.
</div>

<div class="grid gap-4" style="grid-template-columns: 1fr 1fr; max-width: 56rem">
    <form class="vlh-card" method="get" action="{{ route('coins') }}">
        <label class="text-xs" style="color: var(--color-vlh-muted)">Conta (username)</label>
        <input class="vlh-input mb-3" name="username" value="{{ $username }}" placeholder="admin">
        <button class="vlh-btn primary" type="submit">Buscar</button>
        @if ($account)
            <div class="mt-4 text-sm">
                <div>Conta <span class="mono">{{ $account['username'] }}</span></div>
                <div>Coins: <span class="mono">{{ $account['coins'] }}</span></div>
                <div>Time: <span class="mono">{{ $account['time'] }}</span></div>
            </div>
        @elseif ($username !== '')
            <p class="text-sm mt-3 mb-0" style="color: var(--color-vlh-muted)">Conta não encontrada.</p>
        @endif
    </form>

    <form class="vlh-card" method="post" action="{{ route('coins.adjust') }}">
        @csrf
        <input type="hidden" name="username" value="{{ $account['username'] ?? $username }}">
        <label class="text-xs" style="color: var(--color-vlh-muted)">Saldo</label>
        <select class="vlh-input mb-3" name="currency" {{ $account ? '' : 'disabled' }}>
            <option value="coins">Coins</option>
            <option value="time">Time</option>
        </select>
        <label class="text-xs" style="color: var(--color-vlh-muted)">Delta (+/-)</label>
        <input class="vlh-input mb-3" type="number" name="delta" value="{{ old('delta') }}" placeholder="100" {{ $account ? '' : 'disabled' }}>
        <label class="text-xs" style="color: var(--color-vlh-muted)">Motivo</label>
        <input class="vlh-input mb-3" name="reason" value="{{ old('reason') }}" placeholder="ajuste de teste" {{ $account ? '' : 'disabled' }}>
        <label class="text-xs" style="color: var(--color-vlh-muted)">Chave idempotente</label>
        <input class="vlh-input mb-3" name="idempotency_key" value="{{ old('idempotency_key') }}" placeholder="ticket-20260924-001" {{ $account ? '' : 'disabled' }}>
        <button class="vlh-btn primary" {{ $account ? '' : 'disabled' }}>Aplicar</button>
    </form>
</div>
@endsection
