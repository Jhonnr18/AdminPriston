<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\RelicRepository;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RelicController extends Controller
{
    public function __construct(
        private readonly RelicRepository $relics,
    ) {}

    public function index(): View
    {
        $lockedSlot = $this->relics->lockedSlot();
        $existing = collect($this->relics->all())->keyBy('slot');

        // Sempre mostra os 12 slots (0..11), mesmo os que ainda não têm linha
        // em ReliquiaDef — é neles que updateDef() cria a linha pela primeira vez.
        $relics = collect(range(0, 11))
            ->map(fn (int $slot) => $existing->get($slot) ?? [
                'slot' => $slot,
                'name' => '',
                'item' => '',
                'remover' => '',
                'enabled' => false,
                'locked' => $slot === $lockedSlot,
                'bonuses' => [],
                'bonus_rows' => [],
            ])
            ->values()
            ->all();

        return view('admin.reliquias', [
            'relics' => $relics,
            'lockedSlot' => $lockedSlot,
            'pageTitle' => 'Relíquias',
        ]);
    }

    public function updateDef(Request $request, int $slot): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64'],
            'item_code' => ['required', 'string', 'max:10'],
            'remover_code' => ['nullable', 'string', 'max:10'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        try {
            $this->relics->updateDef(
                $slot,
                $data['name'],
                $data['item_code'],
                $data['remover_code'] ?? null,
                (bool) ($data['enabled'] ?? false),
                auth()->user()->name,
                $request->ip(),
            );
        } catch (DomainException $e) {
            return back()->withErrors(['relic_def' => $e->getMessage()])->withInput();
        }

        return redirect()->route('reliquias')->with('status', 'Relíquia atualizada.');
    }

    public function updateBonuses(Request $request, int $slot): RedirectResponse
    {
        $data = $request->validate([
            'bonuses' => ['nullable', 'array'],
            'bonuses.*.type' => ['required', 'integer', 'min:1', 'max:37'],
            'bonuses.*.value' => ['required', 'numeric'],
        ]);

        try {
            $this->relics->updateBonuses(
                $slot,
                $data['bonuses'] ?? [],
                auth()->user()->name,
                $request->ip(),
            );
        } catch (DomainException $e) {
            return back()->withErrors(['relic_bonus' => $e->getMessage()])->withInput();
        }

        return redirect()->route('reliquias')->with('status', 'Bônus da relíquia atualizados.');
    }
}
