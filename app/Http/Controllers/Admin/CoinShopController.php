<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\CoinShopRepository;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoinShopController extends Controller
{
    public function __construct(
        private readonly CoinShopRepository $shops,
    ) {}

    public function index(Request $request): View
    {
        $catalog = $this->shops->catalog(
            $request->integer('shop') ?: null,
            $request->integer('tab') ?: null,
        );

        return view('admin.coin-shop', [
            ...$catalog,
            'pageTitle' => 'Loja de Coins',
        ]);
    }

    public function updateShop(Request $request, int $shop): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'message' => ['nullable', 'string', 'max:500'],
            'discount' => ['required', 'numeric', 'between:0,100'],
            'active' => ['nullable', 'boolean'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);
        try {
            $this->shops->updateShop($shop, $data['name'], $data['message'] ?? '', (float) $data['discount'], (bool) ($data['active'] ?? false), auth()->user()->name, $request->ip(), $data['reason']);
        } catch (DomainException $e) {
            return back()->withErrors(['shop' => $e->getMessage()])->withInput();
        }
        return back()->with('status', 'Loja atualizada e reload solicitado.');
    }

    public function updateTab(Request $request, int $tab): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'discount' => ['required', 'numeric', 'between:0,100'],
            'bulk' => ['nullable', 'boolean'],
            'max_bulk' => ['required', 'integer', 'min:0'],
            'list_order' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);
        try {
            $this->shops->updateTab($tab, $data['name'], (float) $data['discount'], (bool) ($data['bulk'] ?? false), (int) $data['max_bulk'], (int) $data['list_order'], auth()->user()->name, $request->ip(), $data['reason']);
        } catch (DomainException $e) {
            return back()->withErrors(['shop' => $e->getMessage()])->withInput();
        }
        return back()->with('status', 'Aba atualizada e reload solicitado.');
    }

    public function updateItem(Request $request, int $item): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'description' => ['nullable', 'string', 'max:500'],
            'code' => ['required', 'string', 'regex:/^[A-Za-z]{2,3}[0-9]{2,4}$/'],
            'value' => ['required', 'integer', 'min:0'],
            'discount' => ['required', 'numeric', 'between:0,100'],
            'disabled' => ['nullable', 'boolean'],
            'list_order' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);
        try {
            $this->shops->updateItem($item, $data['name'], $data['description'] ?? '', $data['code'], (int) $data['value'], (float) $data['discount'], (bool) ($data['disabled'] ?? false), (int) $data['list_order'], auth()->user()->name, $request->ip(), $data['reason']);
        } catch (DomainException $e) {
            return back()->withErrors(['shop' => $e->getMessage()])->withInput();
        }
        return back()->with('status', 'Item atualizado e reload solicitado.');
    }
}
