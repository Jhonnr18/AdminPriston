<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Model;

class SkillParameterDef extends Model
{
    protected $connection = 'gameserver';
    protected $table = 'SkillParameterDef';
    public $timestamps = false;
    protected $guarded = [];
}
