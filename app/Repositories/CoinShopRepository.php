<?php

namespace App\Repositories;

use App\Models\ShopCoin\CoinShop;
use App\Models\ShopCoin\CoinShopItem;
use App\Models\ShopCoin\CoinShopTab;

class CoinShopRepository
{
    public function __construct(
        private readonly ValhallaDatabase $database,
    ) {}

    /**
     * @return array{shops: list<array<string, mixed>>, tabs: list<array<string, mixed>>, items: list<array<string, mixed>>, shop_id: mixed, tab_id: mixed}
     */
    public function catalog(?int $shopId = null, ?int $tabId = null): array
    {
        if ($this->database->demo() || ! $this->database->shopcoinOnline()) {
            return [
                'shops' => [['id' => 1, 'name' => 'Loja demo', 'active' => true, 'discount' => 0]],
                'tabs' => [
                    ['id' => 1, 'name' => 'Acessorios', 'shop_id' => 1],
                    ['id' => 2, 'name' => 'Premium', 'shop_id' => 1],
                ],
                'items' => [[
                    'id' => 1,
                    'name' => 'Brinco demo',
                    'code' => 'OE101',
                    'value' => 500,
                    'discount' => 0,
                    'disabled' => false,
                ]],
                'shop_id' => 1,
                'tab_id' => 1,
                'offline' => ! $this->database->demo() && ! $this->database->shopcoinOnline(),
            ];
        }

        $shops = CoinShop::query()->orderByDesc('Active')->orderBy('ID')->get()->map(fn ($shop) => [
            'id' => $shop->ID,
            'name' => $shop->Name,
            'message' => $shop->Message,
            'discount' => $shop->Discount,
            'active' => (int) $shop->Active === 1,
        ])->all();

        $shopId ??= $shops[0]['id'] ?? null;
        $tabs = [];
        if ($shopId) {
            $tabs = CoinShopTab::query()
                ->where('CoinShopID', $shopId)
                ->orderBy('ListOrder')
                ->orderBy('ID')
                ->get()
                ->map(fn ($tab) => [
                    'id' => $tab->ID,
                    'name' => $tab->Name,
                    'shop_id' => $tab->CoinShopID,
                    'parent_id' => $tab->ParentID,
                    'discount' => $tab->Discount,
                ])
                ->all();
        }

        $tabId ??= $tabs[0]['id'] ?? null;
        $items = [];
        if ($tabId) {
            $items = CoinShopItem::query()
                ->where('TabID', $tabId)
                ->orderBy('ListOrder')
                ->orderBy('ID')
                ->get()
                ->map(fn ($item) => [
                    'id' => $item->ID,
                    'name' => $item->Name,
                    'code' => $item->Code,
                    'value' => $item->Value,
                    'discount' => $item->Discount,
                    'disabled' => (int) $item->Disabled === 1,
                ])
                ->all();
        }

        return [
            'shops' => $shops,
            'tabs' => $tabs,
            'items' => $items,
            'shop_id' => $shopId,
            'tab_id' => $tabId,
            'offline' => false,
        ];
    }
}
