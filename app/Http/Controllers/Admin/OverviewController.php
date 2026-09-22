<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\OverviewRepository;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __construct(
        private readonly OverviewRepository $overview,
    ) {}

    public function index(): View
    {
        return view('admin.overview', [
            'kpis' => $this->overview->kpis(),
            'problems' => $this->overview->problems(),
            'pageTitle' => 'Visão geral',
        ]);
    }
}
