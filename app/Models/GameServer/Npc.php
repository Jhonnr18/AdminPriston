<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Npc extends GameServerModel
{
    protected $table = 'NpcList';

    protected $primaryKey = 'UniqueID';

    public $incrementing = false;

    protected $fillable = [
        'UniqueID',
        'Model',
        'Name',
        'Code',
        'Active',
        'Size',
        'iLevel',
        'MessageIDs',
        'SellID',
        'Sound',
        'CodeType',
    ];

    public function sellLists(): HasMany
    {
        return $this->hasMany(NpcSellList::class, 'SellID', 'SellID');
    }
}
