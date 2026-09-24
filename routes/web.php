<?php

use App\Http\Controllers\Admin\CoinAdminController;
use App\Http\Controllers\Admin\CoinShopController;
use App\Http\Controllers\Admin\DropController;
use App\Http\Controllers\Admin\FamilyController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\NpcController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\PvpController;
use App\Http\Controllers\Admin\RarityController;
use App\Http\Controllers\Admin\RelicController;
use App\Http\Controllers\Admin\RewardController;
use App\Http\Controllers\Admin\ServerController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\PublicationController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/painel');

Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/media/icon/{code}', [MediaController::class, 'icon'])
    ->where('code', '[A-Za-z]{2,3}[0-9]{2,4}')
    ->name('media.icon');

Route::middleware('auth')->prefix('painel')->group(function () {
    Route::get('/', [OverviewController::class, 'index'])->name('overview');
    Route::get('/itens', [ItemController::class, 'index'])->name('itens');
    Route::get('/itens/{code}', [ItemController::class, 'show'])
        ->where('code', '[A-Za-z]{2,3}[0-9]{2,4}')
        ->name('itens.show');
    Route::post('/itens/{table}/{code}/skin', [ItemController::class, 'updateSkin'])->middleware('panel.permission:rarity.write')
        ->where('table', '[A-Za-z]+')
        ->where('code', '[A-Za-z]{2,3}[0-9]{2,4}')
        ->name('itens.skin.update');
    Route::get('/familia', [FamilyController::class, 'index'])->name('familia');
    Route::get('/drops', [DropController::class, 'index'])->name('drops');
    Route::get('/drops/{monster}/json', [DropController::class, 'json'])
        ->where('monster', '[^/]+')
        ->name('drops.json');
    Route::get('/drops/{monster}', [DropController::class, 'show'])
        ->where('monster', '[^/]+')
        ->name('drops.show');
    Route::get('/skills', [SkillController::class, 'index'])->name('skills');
    Route::post('/skills', [SkillController::class, 'save'])->middleware('panel.permission:skills.write')->name('skills.save');
    Route::get('/skills/sql', [SkillController::class, 'sql'])->name('skills.sql');
    Route::post('/skills/sql', [SkillController::class, 'saveSql'])->middleware('panel.permission:skills.write')->name('skills.sql.save');
    Route::post('/skills/sql/cooldown', [SkillController::class, 'saveCooldown'])->middleware('panel.permission:skills.write')->name('skills.sql.cooldown');
    Route::get('/pvp', [PvpController::class, 'index'])->name('pvp');
    Route::get('/npcs', [NpcController::class, 'index'])->name('npcs');
    Route::get('/npcs/{npc}', [NpcController::class, 'show'])
        ->where('npc', '[0-9]+')
        ->name('npcs.show');
    Route::get('/coin-shop', [CoinShopController::class, 'index'])->name('coin-shop');
    Route::get('/recompensas', [RewardController::class, 'index'])->name('recompensas');
    Route::get('/raridade', [RarityController::class, 'index'])->name('raridade');
    Route::get('/raridade/bonus', [RarityController::class, 'bonuses'])->name('raridade.bonus');
    Route::post('/raridade/bonus', [RarityController::class, 'updateBonus'])->middleware('panel.permission:rarity.write')->name('raridade.bonus.update');
    Route::post('/raridade/grupo/{group}', [RarityController::class, 'updateGroup'])->middleware('panel.permission:rarity.write')
        ->where('group', '[0-9]+')
        ->name('raridade.grupo.update');
    Route::post('/raridade/mod/{type}', [RarityController::class, 'updateMod'])->middleware('panel.permission:rarity.write')
        ->where('type', '[0-9]+')
        ->name('raridade.mod.update');
    Route::get('/reliquias', [RelicController::class, 'index'])->name('reliquias');
    Route::post('/reliquias/{slot}', [RelicController::class, 'updateDef'])->middleware('panel.permission:relics.write')
        ->where('slot', '[0-9]+')
        ->name('reliquias.def.update');
    Route::post('/reliquias/{slot}/bonus', [RelicController::class, 'updateBonuses'])->middleware('panel.permission:relics.write')
        ->where('slot', '[0-9]+')
        ->name('reliquias.bonus.update');
    Route::get('/coins', [CoinAdminController::class, 'index'])->name('coins');
    Route::post('/coins', [CoinAdminController::class, 'adjust'])->middleware('panel.permission:coins.write')->name('coins.adjust');
    Route::get('/servidor', [ServerController::class, 'index'])->name('servidor');
    Route::get('/publicacoes', [PublicationController::class, 'index'])->name('publicacoes');
});
