<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Relations\HasOne;

class Monster extends GameServerModel
{
    protected $table = 'MonsterList';

    protected $primaryKey = 'ID';

    protected $fillable = [
        'Model',
        'MonsterName',
        'MonsterLevel',
        'Active',
        'Boss',
    ];

    public function dropList(): HasOne
    {
        return $this->hasOne(DropList::class, 'MonsterName', 'MonsterName');
    }
}
