<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReliquiaBonus extends GameServerModel
{
    protected $table = 'ReliquiaBonus';

    public $incrementing = false;

    protected $fillable = [
        'RelicIndex',
        'BonusType',
        'Value',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(ReliquiaDef::class, 'RelicIndex', 'RelicIndex');
    }
}
