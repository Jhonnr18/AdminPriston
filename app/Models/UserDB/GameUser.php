<?php

namespace App\Models\UserDB;

use Illuminate\Database\Eloquent\Model;

class GameUser extends Model
{
    protected $connection = 'userdb';

    protected $table = 'Users';

    protected $primaryKey = 'Username';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'UserCoin',
        'UserTime',
    ];

    protected $hidden = [
        'password',
        'Password',
    ];
}
