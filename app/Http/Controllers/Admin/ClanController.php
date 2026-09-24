<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\ClanHealthRepository;
use Illuminate\View\View;

class ClanController extends Controller
{
    public function __construct(private readonly ClanHealthRepository $clans) {}

    public function index(): View
    {
        return view('admin.clans', [
            ...$this->clans->snapshot(),
            'pageTitle' => 'Auditoria de clãs',
        ]);
    }
}
