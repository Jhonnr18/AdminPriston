@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    Vitrine em <span class="mono">ShopCoin.CoinShop*</span>. Após editar: <span class="mono">/reload_shop</span>. Saldo da conta em <span class="mono">UserDB.Users.UserCoin</span> (por username, não personagem).
</div>

<div class="grid gap-4" style="grid-template-columns: 200px 1fr;">
    <div class="vlh-card">
        <div class="text-xs mb-2" style="color: var(--color-vlh-muted)">Abas</div>
        <button class="vlh-btn primary w-full mb-1">Acessorios</button>
        <button class="vlh-btn w-full mb-1">Premium</button>
        <button class="vlh-btn w-full">Time</button>
    </div>
    <div class="vlh-card">
        <table class="vlh-table">
            <thead><tr><th></th><th>Nome</th><th>Code</th><th>Coins</th><th>Desc%</th><th></th></tr></thead>
            <tbody>
                <tr>
                    <td><div class="vlh-icon oe">OE</div></td>
                    <td>Brinco demo</td>
                    <td class="mono">OE101</td>
                    <td>500</td>
                    <td>0</td>
                    <td><span class="vlh-badge">visível</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
