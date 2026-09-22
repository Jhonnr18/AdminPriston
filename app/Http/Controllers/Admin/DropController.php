<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\DropRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DropController extends Controller
{
    public function __construct(
        private readonly DropRepository $drops,
    ) {}

    public function index(): View
    {
        return view('admin.drops', [
            'monsters' => $this->drops->monsters(),
            'pageTitle' => 'Drops dos monstros',
        ]);
    }

    public function show(string $monster): View
    {
        $payload = $this->drops->monsterDrop($monster);

        return view('admin.drop-show', [
            'monster' => $monster,
            'meta' => $payload['meta'],
            'rows' => $payload['rows'],
            'sharedWith' => $payload['shared_with'],
            'pageTitle' => 'Drop · '.$monster,
        ]);
    }

    public function json(string $monster): JsonResponse
    {
        return response()->json(\App\Support\Utf8::clean($this->drops->monsterDrop($monster)));
    }
}
