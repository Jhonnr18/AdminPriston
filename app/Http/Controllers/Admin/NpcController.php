<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\ShopRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NpcController extends Controller
{
    public function __construct(
        private readonly ShopRepository $shops,
    ) {}

    public function index(Request $request): View
    {
        $npcs = $this->shops->npcIndex();
        $selectedId = $request->integer('npc') ?: ($npcs[0]['id'] ?? null);
        $selected = $selectedId !== null ? $this->shops->npcDetail($selectedId) : null;
        $tab = $request->integer('tab') ?: 1;

        return view('admin.npcs', [
            'npcs' => $npcs,
            'selected' => $selected,
            'tab' => $tab,
            'sellTypes' => config('valhalla.npc_sell_types'),
            'pageTitle' => 'NPCs e Lojas',
        ]);
    }

    public function show(int|string $npc): JsonResponse
    {
        $exact = collect($this->shops->npcIndex())
            ->first(fn ($row) => (string) $row['id'] === (string) $npc);
        if (! $exact) {
            return response()->json(['error' => 'NPC não encontrado'], 404);
        }

        return response()->json(\App\Support\Utf8::clean($this->shops->npcDetail($exact['id'])));
    }
}
