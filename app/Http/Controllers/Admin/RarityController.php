<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\RarityRepository;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RarityController extends Controller
{
    public function __construct(
        private readonly RarityRepository $rarity,
    ) {}

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
                'operador',
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
                'operador',
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
