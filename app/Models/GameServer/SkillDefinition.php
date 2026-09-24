<?php

namespace App\Models\GameServer;

use Illuminate\Database\Eloquent\Model;

class SkillDefinition extends Model
{
    protected $connection = 'gameserver';
    protected $table = 'SkillDefinition';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['Active' => 'boolean'];
}
