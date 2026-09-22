<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\CoinShopRepository;
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
}
