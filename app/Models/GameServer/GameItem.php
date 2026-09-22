<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Builder;

class GameItem extends GameServerModel
{
    protected $table = 'Weapons';

    protected $primaryKey = 'ID';

    protected $guarded = [];

    public static function queryTable(string $table): Builder
    {
        $instance = new static;
        $instance->setTable($table);

        return $instance->newQuery();
    }

    public function isProtectedFamily(): bool
    {
        $code = strtoupper((string) ($this->Code ?? ''));

        return str_starts_with($code, 'WV') && (bool) config('valhalla.protect_wv');
    }
}
