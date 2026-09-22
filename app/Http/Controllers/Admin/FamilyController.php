<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\ItemRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FamilyController extends Controller
{
    public function __construct(
        private readonly ItemRepository $items,
    ) {}

    public function index(Request $request): View
    {
        $prefix = strtoupper($request->string('prefix', 'WA')->toString());
        $meta = $this->items->familyMeta($prefix);

        return view('admin.familia', [
            'prefix' => $prefix,
            'meta' => $meta,
            'families' => config('valhalla.families'),
            'items' => $this->items->family($prefix),
            'protectWv' => (bool) config('valhalla.protect_wv'),
            'pageTitle' => 'Linha da família',
        ]);
    }
}
