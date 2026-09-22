<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Model;

abstract class GameServerModel extends Model
{
    protected $connection = 'gameserver';

    public $timestamps = false;
}
