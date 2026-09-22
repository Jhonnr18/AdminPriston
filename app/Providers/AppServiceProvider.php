<?php

namespace App\Providers;

use App\Repositories\ClientAssetRepository;
use App\Repositories\ItemRepository;
use App\Repositories\ItemsHRepository;
use App\Repositories\ValhallaDatabase;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ValhallaDatabase::class);
        $this->app->singleton(ItemRepository::class);
        $this->app->singleton(ItemsHRepository::class);
        $this->app->singleton(ClientAssetRepository::class);
    }

    public function boot(): void
    {
        View::composer('layouts.admin', function ($view) {
            $database = app(ValhallaDatabase::class);
            $view->with('sqlStatus', $database->statusLabel());
            $view->with('sqlWarning', $database->warning());
            $view->with('sqlDemo', $database->demo());
        });
    }
}
