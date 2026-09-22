<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RarityChance extends GameServerModel
{
    protected $table = 'RarityChance';

    public $incrementing = false;

    protected $fillable = [
        'RarityChanceGroup',
        'Rarity',
        'Chance',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(RarityChanceGroup::class, 'RarityChanceGroup', 'ID');
    }
}
