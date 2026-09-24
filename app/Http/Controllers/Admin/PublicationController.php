<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ConfigPublicationService;
use Illuminate\View\View;
use Throwable;

class PublicationController extends Controller
{
    public function __construct(private readonly ConfigPublicationService $publications) {}

    public function index(): View
    {
        try {
            $reloads = $this->publications->recentReloads();
            $error = null;
        } catch (Throwable $e) {
            $reloads = [];
            $error = 'PainelDB indisponível ou ainda não aplicado neste ambiente.';
        }

        return view('admin.publicacoes', [
            'reloads' => $reloads,
            'publicationError' => $error,
            'pageTitle' => 'Publicações e reloads',
        ]);
    }
}
