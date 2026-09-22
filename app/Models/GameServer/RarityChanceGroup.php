<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Relations\HasMany;

class RarityChanceGroup extends GameServerModel
{
    protected $table = 'RarityChanceGroup';

    protected $primaryKey = 'ID';

    public $incrementing = false;

    protected $fillable = [
        'ID',
        'MinLevel',
        'MaxLevel',
    ];

    public function chances(): HasMany
    {
        return $this->hasMany(RarityChance::class, 'RarityChanceGroup', 'ID');
    }
}
