<?php

namespace App\Repositories;

use App\Models\ShopCoin\CoinShop;
use App\Models\ShopCoin\CoinShopItem;
use App\Models\ShopCoin\CoinShopTab;
use App\Services\ConfigPublicationService;
use DomainException;
use Illuminate\Support\Facades\DB;

class CoinShopRepository
{
    public function __construct(
        private readonly ValhallaDatabase $database,
        private readonly ConfigPublicationService $publication,
    ) {}

    public function updateShop(int $id, string $name, string $message, float $discount, bool $active, string $operator, ?string $ip, string $reason): void
    {
        if ($this->database->demo() || ! $this->database->shopcoinOnline()) {
            throw new DomainException('ShopCoin indisponível; edição exige SQL Server.');
        }
        if ($discount < 0 || $discount > 100) {
            throw new DomainException('Desconto da loja deve ficar entre 0 e 100.');
        }

        $before = null;
        DB::connection('shopcoin')->transaction(function () use ($id, $name, $message, $discount, $active, &$before): void {
            $shop = CoinShop::query()->where('ID', $id)->lockForUpdate()->first();
            if (! $shop) {
                throw new DomainException('Loja não encontrada.');
            }
            $before = ['name' => $shop->Name, 'message' => $shop->Message, 'discount' => (float) $shop->Discount, 'active' => (bool) $shop->Active];
            $shop->update(['Name' => trim($name), 'Message' => trim($message), 'Discount' => $discount, 'Active' => $active ? 1 : 0]);
        });
        $this->publish('shop:'.$id, $before, compact('name', 'message', 'discount', 'active'), $operator, $ip, $reason);
    }

    public function updateTab(int $id, string $name, float $discount, bool $bulk, int $maxBulk, int $listOrder, string $operator, ?string $ip, string $reason): void
    {
        if ($this->database->demo() || ! $this->database->shopcoinOnline()) {
            throw new DomainException('ShopCoin indisponível; edição exige SQL Server.');
        }
        if ($discount < 0 || $discount > 100 || $maxBulk < 0 || $listOrder < 0) {
            throw new DomainException('Desconto, quantidade máxima e ordem precisam ser válidos.');
        }
        $before = null;
        DB::connection('shopcoin')->transaction(function () use ($id, $name, $discount, $bulk, $maxBulk, $listOrder, &$before): void {
            $tab = CoinShopTab::query()->where('ID', $id)->lockForUpdate()->first();
            if (! $tab) {
                throw new DomainException('Aba não encontrada.');
            }
            $before = ['name' => $tab->Name, 'discount' => (float) $tab->Discount, 'bulk' => (bool) $tab->Bulk, 'max_bulk' => (int) $tab->MaxBulk, 'list_order' => (int) $tab->ListOrder];
            $tab->update(['Name' => trim($name), 'Discount' => $discount, 'Bulk' => $bulk ? 1 : 0, 'MaxBulk' => $maxBulk, 'ListOrder' => $listOrder]);
        });
        $this->publish('tab:'.$id, $before, compact('name', 'discount', 'bulk', 'maxBulk', 'listOrder'), $operator, $ip, $reason);
    }

    public function updateItem(int $id, string $name, string $description, string $code, int $value, float $discount, bool $disabled, int $listOrder, string $operator, ?string $ip, string $reason): void
    {
        if ($this->database->demo() || ! $this->database->shopcoinOnline()) {
            throw new DomainException('ShopCoin indisponível; edição exige SQL Server.');
        }
        $code = strtoupper(trim($code));
        if (! preg_match('/^[A-Z]{2,3}[0-9]{2,4}$/', $code) || $value < 0 || $discount < 0 || $discount > 100 || $listOrder < 0) {
            throw new DomainException('Código, preço, desconto ou ordem inválidos.');
        }
        $before = null;
        DB::connection('shopcoin')->transaction(function () use ($id, $name, $description, $code, $value, $discount, $disabled, $listOrder, &$before): void {
            $item = CoinShopItem::query()->where('ID', $id)->lockForUpdate()->first();
            if (! $item) {
                throw new DomainException('Item da loja não encontrado.');
            }
            $before = ['name' => $item->Name, 'description' => $item->Description, 'code' => $item->Code, 'value' => (int) $item->Value, 'discount' => (float) $item->Discount, 'disabled' => (bool) $item->Disabled, 'list_order' => (int) $item->ListOrder];
            $item->update(['Name' => trim($name), 'Description' => trim($description), 'Code' => $code, 'Value' => $value, 'Discount' => $discount, 'Disabled' => $disabled ? 1 : 0, 'ListOrder' => $listOrder]);
        });
        $this->publish('item:'.$id, $before, compact('name', 'description', 'code', 'value', 'discount', 'disabled', 'listOrder'), $operator, $ip, $reason);
    }

    private function publish(string $target, array $before, array $after, string $operator, ?string $ip, string $reason): void
    {
        $versionId = $this->publication->recordMutation('shop_coin', $target, $before, $after, $operator, $ip, $reason);
        if ($versionId !== null) {
            try {
                $this->publication->queueReload('shop_coin', $versionId, $operator);
                return;
            } catch (\Throwable) {
                // A API ainda pode ser chamada pelo fallback abaixo.
            }
        } else {
            $this->publication->queueReloadBestEffort('shop_coin', $operator);
            return;
        }
        $this->publication->queueReloadBestEffort('shop_coin', $operator);
    }

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
                    'description' => $item->Description,
                    'code' => $item->Code,
                    'value' => $item->Value,
                    'discount' => $item->Discount,
                    'disabled' => (int) $item->Disabled === 1,
                    'list_order' => (int) $item->ListOrder,
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
