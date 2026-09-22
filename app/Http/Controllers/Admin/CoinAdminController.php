<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\CoinAdminRepository;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoinAdminController extends Controller
{
    public function __construct(
        private readonly CoinAdminRepository $coins,
    ) {}

    public function index(Request $request): View
    {
        $username = $request->string('username')->toString();

        return view('admin.coins', [
            'username' => $username,
            'account' => $username !== '' ? $this->coins->find($username) : null,
            'pageTitle' => 'Coins e Time',
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'delta' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        try {
            $this->coins->adjustCoins(
                $data['username'],
                (int) $data['delta'],
                $data['reason'],
                'operador',
                $request->ip(),
            );
        } catch (DomainException $e) {
            return back()->withErrors(['coins' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('coins', ['username' => $data['username']])
            ->with('status', 'Saldo atualizado.');
    }
}
