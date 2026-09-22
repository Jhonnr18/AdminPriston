<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Relations\HasMany;

class NpcSellList extends GameServerModel
{
    protected $table = 'NpcSellList';

    protected $primaryKey = 'ID';

    protected $fillable = [
        'SellID',
        'ItemType',
        'ItemList',
    ];

    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class, 'SellID', 'SellID');
    }
}
