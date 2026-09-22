<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DropItem extends GameServerModel
{
    protected $table = 'DropItem';

    protected $primaryKey = 'ID';

    protected $fillable = [
        'DropID',
        'Items',
        'Chance',
        'GoldMin',
        'GoldMax',
    ];

    public function dropList(): BelongsTo
    {
        return $this->belongsTo(DropList::class, 'DropID', 'DropID');
    }
}
