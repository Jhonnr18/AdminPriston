<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PvpController extends Controller
{
    public function index(): View
    {
        return view('admin.pvp', ['pageTitle' => 'Dano em PvP']);
    }
}
