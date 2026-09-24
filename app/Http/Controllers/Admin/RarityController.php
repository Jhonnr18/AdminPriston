<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\RarityRepository;
use App\Repositories\RarityBonusRepository;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RarityController extends Controller
{
    public function __construct(
        private readonly RarityRepository $rarity,
        private readonly RarityBonusRepository $bonuses,
    ) {}

    public function bonuses(): View
    {
        return view('admin.raridade-bonus', [
            'rows' => $this->bonuses->all(),
            'bands' => config('valhalla.rarity_bonus_bands'),
            'stats' => config('valhalla.rarity_bonus_stats'),
            'pageTitle' => 'Bônus de raridade',
        ]);
    }

    public function updateBonus(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rarity' => ['required', 'integer', 'between:2,5'],
            'band' => ['required', 'string'],
            'stat' => ['required', 'string'],
            'value' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $this->bonuses->update(
                (int) $data['rarity'],
                $data['band'],
                $data['stat'],
                $data['value'],
                auth()->user()->name,
                $request->ip(),
                $data['reason'],
            );
        } catch (DomainException $e) {
            return back()->withErrors(['rarity_bonus' => $e->getMessage()])->withInput();
        }

        return redirect()->route('raridade.bonus')->with('status', 'Bônus de raridade atualizado.');
    }

    public function index(): View
    {
        return view('admin.raridade', [
            'groups' => $this->rarity->groups(),
            'modifiers' => $this->rarity->modifiers(),
            'denominator' => $this->rarity->denominator(),
            'pageTitle' => 'Raridade',
        ]);
    }

    public function updateGroup(Request $request, int $group): RedirectResponse
    {
        $data = $request->validate([
            'min' => ['required', 'integer', 'min:0'],
            'max' => ['required', 'integer', 'min:0'],
            'uncommon' => ['required', 'integer', 'min:0'],
            'rare' => ['required', 'integer', 'min:0'],
            'epic' => ['required', 'integer', 'min:0'],
            'legendary' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $this->rarity->updateGroup(
                $group,
                (int) $data['min'],
                (int) $data['max'],
                [
                    'uncommon' => (int) $data['uncommon'],
                    'rare' => (int) $data['rare'],
                    'epic' => (int) $data['epic'],
                    'legendary' => (int) $data['legendary'],
                ],
                auth()->user()->name,
                $request->ip(),
            );
        } catch (DomainException $e) {
            return back()->withErrors(['raridade' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('raridade')
            ->with('status', 'Raridade atualizada.');
    }

    public function updateMod(Request $request, int $type): RedirectResponse
    {
        $data = $request->validate([
            'common' => ['required', 'numeric'],
            'uncommon' => ['required', 'numeric'],
            'rare' => ['required', 'numeric'],
            'epic' => ['required', 'numeric'],
            'legendary' => ['required', 'numeric'],
        ]);

        try {
            $this->rarity->updateMod(
                $type,
                [
                    'common' => (float) $data['common'],
                    'uncommon' => (float) $data['uncommon'],
                    'rare' => (float) $data['rare'],
                    'epic' => (float) $data['epic'],
                    'legendary' => (float) $data['legendary'],
                ],
                auth()->user()->name,
                $request->ip(),
            );
        } catch (DomainException $e) {
            return back()->withErrors(['raridade' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('raridade')
            ->with('status', 'Raridade atualizada.');
    }
}
