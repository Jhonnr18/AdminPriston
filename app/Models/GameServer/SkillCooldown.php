<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Model;

class SkillCooldown extends Model
{
    protected $connection = 'gameserver';
    protected $table = 'SkillCooldown';
    public $timestamps = false;
    protected $guarded = [];
}
