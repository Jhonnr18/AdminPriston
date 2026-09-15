<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DemoCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function overview(): View
    {
        return view('admin.overview', [
            'kpis' => DemoCatalog::kpis(),
            'problems' => DemoCatalog::problems(),
            'pageTitle' => 'Visão geral',
        ]);
    }

    public function itens(Request $request): View
    {
        $table = $request->string('table', 'Weapons')->toString();

        return view('admin.itens', [
            'tables' => DemoCatalog::itemTables(),
            'table' => $table,
            'items' => DemoCatalog::weaponFamily($table === 'Weapons' ? 'WA' : 'WA'),
            'pageTitle' => 'Itens',
        ]);
    }

    public function familia(Request $request): View
    {
        $prefix = strtoupper($request->string('prefix', 'WA')->toString());

        return view('admin.familia', [
            'prefix' => $prefix,
            'items' => DemoCatalog::weaponFamily($prefix),
            'protectWv' => (bool) config('valhalla.protect_wv'),
            'pageTitle' => 'Linha da família',
        ]);
    }

    public function drops(): View
    {
        return view('admin.drops', [
            'monsters' => DemoCatalog::monsters(),
            'pageTitle' => 'Drops dos monstros',
        ]);
    }

    public function dropShow(string $monster): View
    {
        return view('admin.drop-show', [
            'monster' => $monster,
            'meta' => collect(DemoCatalog::monsters())->firstWhere('name', $monster) ?? [
                'name' => $monster, 'level' => '?', 'drop_id' => '?', 'rows' => 0, 'boss' => false,
            ],
            'rows' => DemoCatalog::dropRows($monster),
            'pageTitle' => 'Drop · '.$monster,
        ]);
    }

    public function skills(): View
    {
        return view('admin.skills', [
            'param' => DemoCatalog::skillParams(),
            'pageTitle' => 'Skills',
        ]);
    }

    public function pvp(): View
    {
        return view('admin.pvp', ['pageTitle' => 'Dano em PvP']);
    }

    public function npcs(): View
    {
        return view('admin.npcs', ['pageTitle' => 'NPCs e Lojas']);
    }

    public function coinShop(): View
    {
        return view('admin.coin-shop', ['pageTitle' => 'Loja de Coins']);
    }

    public function recompensas(): View
    {
        return view('admin.recompensas', ['pageTitle' => 'Recompensas']);
    }

    public function raridade(): View
    {
        return view('admin.raridade', ['pageTitle' => 'Raridade']);
    }

    public function reliquias(): View
    {
        return view('admin.reliquias', ['pageTitle' => 'Relíquias']);
    }

    public function coins(): View
    {
        return view('admin.coins', ['pageTitle' => 'Coins e Time']);
    }

    public function servidor(): View
    {
        return view('admin.servidor', [
            'pageTitle' => 'Servidor',
            'config' => [
                'host' => config('valhalla.sqlsrv.host'),
                'port' => config('valhalla.sqlsrv.port'),
                'database' => config('valhalla.sqlsrv.database'),
                'client_items' => config('valhalla.client_items_root'),
                'dropitem' => config('valhalla.client_dropitem_root'),
                'items_h' => config('valhalla.items_h_path'),
                'skills' => config('valhalla.skills_path'),
                'icon_tpl' => config('valhalla.icon_filename_tpl'),
                'drop_tpl' => config('valhalla.drop_mesh_tpl'),
                'subfolders' => config('valhalla.icon_subfolders'),
                'protect_wv' => config('valhalla.protect_wv'),
                'demo' => config('valhalla.demo_mode'),
            ],
        ]);
    }
}
