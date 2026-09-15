@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    Dar/tirar coins grava <span class="mono">UserDB.Users.UserCoin</span> na hora (sem cache). Toda operação deve ir para auditoria <span class="mono">ValhallaAdmin.audit</span> (schema próprio — não GameServer).
</div>

<div class="vlh-card" style="max-width:28rem">
    <label class="text-xs" style="color: var(--color-vlh-muted)">Conta (username)</label>
    <input class="vlh-input mb-3" placeholder="admin" disabled>
    <label class="text-xs" style="color: var(--color-vlh-muted)">Delta (+/-)</label>
    <input class="vlh-input mb-3" type="number" placeholder="100" disabled>
    <button class="vlh-btn primary" disabled>Aplicar (requer coins.grant)</button>
</div>
@endsection
