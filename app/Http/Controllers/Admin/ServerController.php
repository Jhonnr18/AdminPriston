<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\ServerConfigRepository;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function __construct(
        private readonly ServerConfigRepository $server,
    ) {}

    public function index(): View
    {
        return view('admin.servidor', [
            'pageTitle' => 'Servidor',
            'config' => $this->server->snapshot(),
        ]);
    }
}
