<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DropList extends GameServerModel
{
    protected $table = 'DropList';

    protected $primaryKey = 'ID';

    protected $fillable = [
        'DropID',
        'MonsterName',
        'PublicDrop',
        'Quantity',
    ];

    public function monster(): BelongsTo
    {
        return $this->belongsTo(Monster::class, 'MonsterName', 'MonsterName');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DropItem::class, 'DropID', 'DropID');
    }
}
