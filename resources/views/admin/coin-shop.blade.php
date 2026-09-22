@extends('layouts.admin')

@section('content')
<div class="vlh-callout mb-4">
    Vitrine em <span class="mono">ShopCoin.CoinShop*</span>. Após editar: <span class="mono">/reload_shop</span>. Saldo da conta em <span class="mono">UserDB.Users.UserCoin</span> (por username, não personagem).
</div>

@if (!empty($offline))
    <div class="vlh-callout danger mb-4">Banco ShopCoin indisponível.</div>
@endif

<div class="flex flex-wrap gap-2 mb-4">
    @foreach ($shops as $shop)
        <a href="{{ route('coin-shop', ['shop' => $shop['id']]) }}"
           class="vlh-btn {{ (int) $shop_id === (int) $shop['id'] ? 'primary' : '' }}">
            {{ $shop['name'] }}
            @if (!empty($shop['active'])) <span class="vlh-badge gold">ativa</span> @endif
        </a>
    @endforeach
</div>

<div class="grid gap-4" style="grid-template-columns: 200px 1fr;">
    <div class="vlh-card">
        <div class="text-xs mb-2" style="color: var(--color-vlh-muted)">Abas</div>
        @forelse ($tabs as $tabRow)
            <a href="{{ route('coin-shop', ['shop' => $shop_id, 'tab' => $tabRow['id']]) }}"
               class="vlh-btn w-full mb-1 {{ (int) $tab_id === (int) $tabRow['id'] ? 'primary' : '' }}">
                {{ $tabRow['name'] }}
            </a>
        @empty
            <p class="text-sm m-0" style="color: var(--color-vlh-muted)">Sem abas.</p>
        @endforelse
    </div>
    <div class="vlh-card">
        <table class="vlh-table">
            <thead><tr><th></th><th>Nome</th><th>Code</th><th>Coins</th><th>Desc%</th><th></th></tr></thead>
            <tbody>
            @forelse ($items as $item)
                <tr class="vlh-open" data-vlh-modal="item" data-vlh-id="{{ $item['code'] }}" role="button" tabindex="0">
                    <td>@include('components.item-icon', ['code' => $item['code'], 'size' => 40])</td>
                    <td>{{ $item['name'] }}</td>
                    <td class="mono">{{ $item['code'] }}</td>
                    <td>{{ $item['value'] }}</td>
                    <td>{{ $item['discount'] }}</td>
                    <td>
                        @if (!empty($item['disabled']))
                            <span class="vlh-badge">oculto</span>
                        @else
                            <span class="vlh-badge gold">visível</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="color: var(--color-vlh-muted)">Nenhum item nesta aba.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
