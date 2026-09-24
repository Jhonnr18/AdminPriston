<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Model;

class SkillLevelValue extends Model
{
    protected $connection = 'gameserver';
    protected $table = 'SkillLevelValue';
    public $timestamps = false;
    protected $guarded = [];
}
