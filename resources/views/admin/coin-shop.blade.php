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
            <details class="mb-2"><summary class="text-xs">Editar aba</summary>
                <form method="post" action="{{ route('coin-shop.tab.update', $tabRow['id']) }}" class="mt-2">
                    @csrf
                    <input class="vlh-input mb-1" name="name" value="{{ $tabRow['name'] }}" placeholder="Nome">
                    <input class="vlh-input mb-1" type="number" step="any" min="0" max="100" name="discount" value="{{ $tabRow['discount'] }}" placeholder="Desconto">
                    <input class="vlh-input mb-1" type="number" min="0" name="max_bulk" value="0" placeholder="Máximo">
                    <input class="vlh-input mb-1" type="number" min="0" name="list_order" value="0" placeholder="Ordem">
                    <input class="vlh-input mb-1" name="reason" minlength="5" placeholder="Motivo" required>
                    <label class="text-xs"><input type="checkbox" name="bulk" value="1"> bulk</label>
                    <button class="vlh-btn primary w-full mt-1" type="submit">Salvar aba</button>
                </form>
            </details>
        @empty
            <p class="text-sm m-0" style="color: var(--color-vlh-muted)">Sem abas.</p>
        @endforelse
    </div>
    <div class="vlh-card">
        @if ($shop_id)
            @php($currentShop = collect($shops)->firstWhere('id', $shop_id))
            @if ($currentShop)
                <form method="post" action="{{ route('coin-shop.shop.update', $shop_id) }}" class="mb-4">
                    @csrf
                    <div class="grid gap-2" style="grid-template-columns: 2fr 1fr;">
                        <input class="vlh-input" name="name" value="{{ $currentShop['name'] }}" placeholder="Nome">
                        <input class="vlh-input" type="number" step="any" min="0" max="100" name="discount" value="{{ $currentShop['discount'] }}" placeholder="Desconto %">
                    </div>
                    <input class="vlh-input mt-2" name="message" value="{{ $currentShop['message'] ?? '' }}" placeholder="Mensagem">
                    <label class="text-xs mt-2 block"><input type="checkbox" name="active" value="1" @checked($currentShop['active'])> ativa</label>
                    <input class="vlh-input mt-2" name="reason" minlength="5" placeholder="Motivo da alteração" required>
                    <button class="vlh-btn primary mt-2" type="submit">Salvar loja</button>
                </form>
            @endif
        @endif
        <table class="vlh-table">
            <thead><tr><th></th><th>Nome</th><th>Code</th><th>Coins</th><th>Desc%</th><th></th></tr></thead>
            <tbody>
            @forelse ($items as $item)
                <tr>
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
                <tr>
                    <td colspan="6">
                        <details><summary>Editar item</summary>
                            <form method="post" action="{{ route('coin-shop.item.update', $item['id']) }}" class="grid gap-2 mt-2" style="grid-template-columns: 2fr 1fr 1fr 1fr;">
                                @csrf
                                <input class="vlh-input" name="name" value="{{ $item['name'] }}" placeholder="Nome">
                                <input class="vlh-input" name="code" value="{{ $item['code'] }}" placeholder="Code">
                                <input class="vlh-input" type="number" min="0" name="value" value="{{ $item['value'] }}" placeholder="Coins">
                                <input class="vlh-input" type="number" step="any" min="0" max="100" name="discount" value="{{ $item['discount'] }}" placeholder="Desc%">
                                <input class="vlh-input" name="description" value="{{ $item['description'] ?? '' }}" placeholder="Descrição">
                                <input class="vlh-input" type="number" min="0" name="list_order" value="{{ $item['list_order'] ?? 0 }}" placeholder="Ordem">
                                <input class="vlh-input" name="reason" minlength="5" placeholder="Motivo da alteração" required>
                                <label class="text-xs"><input type="checkbox" name="disabled" value="1" @checked($item['disabled'])> oculto</label>
                                <button class="vlh-btn primary" type="submit">Salvar item</button>
                            </form>
                        </details>
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
