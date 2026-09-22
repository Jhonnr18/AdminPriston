<?php

namespace App\Console\Commands;

use App\Repositories\ValhallaDatabase;
use Illuminate\Console\Command;

class ValhallaProbeCommand extends Command
{
    protected $signature = 'valhalla:probe';

    protected $description = 'Testa as conexões GameServer, UserDB e ShopCoin sem alterar dados';

    public function handle(ValhallaDatabase $database): int
    {
        $this->info('Demo: '.($database->demo() ? 'sim' : 'não'));
        $this->info('Host: '.$database->hostLabel());
        $this->info('GameServer: '.($database->gameserverOnline() ? 'online' : 'offline'));
        $this->info('UserDB: '.($database->userdbOnline() ? 'online' : 'offline'));
        $this->info('ShopCoin: '.($database->shopcoinOnline() ? 'online' : 'offline'));

        if ($warning = $database->warning()) {
            $this->warn($warning);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
