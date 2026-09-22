<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class ValhallaDatabase
{
    private ?bool $gameserver = null;

    private ?bool $userdb = null;

    private ?bool $shopcoin = null;

    public function demo(): bool
    {
        return (bool) config('valhalla.demo_mode');
    }

    public function gameserverOnline(): bool
    {
        return $this->gameserver ??= $this->cachedPing('gameserver');
    }

    public function userdbOnline(): bool
    {
        return $this->userdb ??= $this->cachedPing('userdb');
    }

    public function shopcoinOnline(): bool
    {
        return $this->shopcoin ??= $this->cachedPing('shopcoin');
    }

    public function usingFixtures(): bool
    {
        return $this->demo() || ! $this->gameserverOnline();
    }

    public function statusLabel(): string
    {
        if ($this->demo()) {
            return 'DEMO (fixtures)';
        }

        return $this->gameserverOnline() ? 'SQL Server' : 'SQL OFFLINE';
    }

    public function warning(): ?string
    {
        if ($this->demo()) {
            return null;
        }

        if ($this->gameserverOnline()) {
            return null;
        }

        return 'Não foi possível conectar em GameServer ('.$this->hostLabel().'). Conferir Docker valhalla_pt_sqlserver, senha no .env e extensão pdo_odbc/sqlsrv.';
    }

    public function hostLabel(): string
    {
        return config('valhalla.sqlsrv.host').','.config('valhalla.sqlsrv.port');
    }

    private function cachedPing(string $connection): bool
    {
        if ($this->demo()) {
            return false;
        }

        return (bool) Cache::remember('valhalla.ping.'.$connection, 30, function () use ($connection) {
            try {
                DB::connection($connection)->select('SELECT 1 AS ok');

                return true;
            } catch (Throwable) {
                return false;
            }
        });
    }
}
