<?php

use App\Http\Controllers\Admin\PanelController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/painel');

Route::prefix('painel')->group(function () {
    Route::get('/', [PanelController::class, 'overview'])->name('overview');
    Route::get('/itens', [PanelController::class, 'itens'])->name('itens');
    Route::get('/familia', [PanelController::class, 'familia'])->name('familia');
    Route::get('/drops', [PanelController::class, 'drops'])->name('drops');
    Route::get('/drops/{monster}', [PanelController::class, 'dropShow'])->name('drops.show');
    Route::get('/skills', [PanelController::class, 'skills'])->name('skills');
    Route::get('/pvp', [PanelController::class, 'pvp'])->name('pvp');
    Route::get('/npcs', [PanelController::class, 'npcs'])->name('npcs');
    Route::get('/coin-shop', [PanelController::class, 'coinShop'])->name('coin-shop');
    Route::get('/recompensas', [PanelController::class, 'recompensas'])->name('recompensas');
    Route::get('/raridade', [PanelController::class, 'raridade'])->name('raridade');
    Route::get('/reliquias', [PanelController::class, 'reliquias'])->name('reliquias');
    Route::get('/coins', [PanelController::class, 'coins'])->name('coins');
    Route::get('/servidor', [PanelController::class, 'servidor'])->name('servidor');
});
