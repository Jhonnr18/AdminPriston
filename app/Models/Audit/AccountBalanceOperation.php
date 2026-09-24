<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class AccountBalanceOperation extends Model
{
    protected $table = 'account_balance_operations';

    protected $fillable = [
        'idempotency_key', 'username', 'currency', 'delta',
        'before_balance', 'after_balance', 'operator', 'ip',
        'reason', 'status',
    ];

    protected $casts = [
        'delta' => 'integer',
        'before_balance' => 'integer',
        'after_balance' => 'integer',
    ];
}
