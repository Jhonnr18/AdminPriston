<?php

namespace App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

class ConfigAudit extends Model
{
    protected $table = 'valhalla_audits';

    protected $fillable = [
        'operator',
        'ip',
        'action',
        'resource',
        'target_key',
        'value_before',
        'value_after',
        'note',
        'result',
    ];
}
