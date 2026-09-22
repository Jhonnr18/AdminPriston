<?php

namespace App\Models\GameServer;

class RarityChanceMod extends GameServerModel
{
    protected $table = 'RarityChanceMod';

    protected $primaryKey = 'Type';

    public $incrementing = false;

    protected $fillable = [
        'Type',
        'ModCommon',
        'ModUncommon',
        'ModRare',
        'ModEpic',
        'ModLegendary',
    ];
}
