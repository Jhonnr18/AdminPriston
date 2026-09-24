<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\ItemRepository;
use App\Repositories\ItemSkinRepository;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function __construct(
        private readonly ItemRepository $items,
        private readonly ItemSkinRepository $skins,
    ) {}

    public function index(Request $request): View
    {
        $table = $request->string('table', 'Weapons')->toString();
        $search = $request->string('q')->toString();
        $page = max(1, $request->integer('page') ?: 1);
        if (! in_array($table, $this->items->tables(), true)) {
            $table = 'Weapons';
        }

        $listing = $this->items->listByTable(
            $table,
            $search !== '' ? $search : null,
            $page,
            50,
        );

        return view('admin.itens', [
            'tables' => $this->items->tableCounts(),
            'table' => $table,
            'search' => $search,
            'items' => $listing['items'],
            'page' => $listing['page'],
            'lastPage' => $listing['last_page'],
            'total' => $listing['total'],
            'pageTitle' => 'Itens',
        ]);
    }

    public function show(string $code): JsonResponse
    {
        $detail = $this->items->detail($code);
        if ($detail === null) {
            return response()->json(['error' => 'Item não encontrado'], 404);
        }

        $detail['skin'] = [
            'current' => $this->skins->currentSkin($detail['table'], $detail['code']),
            'available' => $this->skins->availableSkinsFor($detail['code']),
        ];

        return response()->json(\App\Support\Utf8::clean($detail));
    }

    // TODO: gate por permissão item_skin_editor quando o sistema de permissões existir
    // (nenhum controller de escrita do painel checa permissão hoje — ver RelicController,
    // RarityController, SkillController, CoinAdminController).
    public function updateSkin(Request $request, string $table, string $code): JsonResponse
    {
        $data = $request->validate([
            'skin_code' => ['nullable', 'string', 'max:10'],
        ]);

        try {
            $result = $this->skins->setSkin(
                $table,
                $code,
                $data['skin_code'] ?? null,
                auth()->user()->name,
                $request->ip(),
            );
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(\App\Support\Utf8::clean($result));
    }
}
