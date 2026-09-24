<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Model;

class RarityBonus extends Model
{
    protected $connection = 'gameserver';
    protected $table = 'RarityBonus';
    public $timestamps = false;
    protected $guarded = [];
}
