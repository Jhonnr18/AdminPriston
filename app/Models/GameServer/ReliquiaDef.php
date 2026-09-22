<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ReliquiaDef extends GameServerModel
{
    protected $table = 'ReliquiaDef';

    protected $primaryKey = 'RelicIndex';

    public $incrementing = false;

    protected $fillable = [
        'RelicIndex',
        'Enabled',
        'Name',
        'ItemCode',
        'RemoverCode',
    ];

    public function bonuses(): HasMany
    {
        return $this->hasMany(ReliquiaBonus::class, 'RelicIndex', 'RelicIndex');
    }

    public function isLockedSlot(): bool
    {
        return (int) $this->RelicIndex === (int) config('valhalla.relic_locked_slot');
    }
}
