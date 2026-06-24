<?php

namespace Modules\AfisEngine\Models;

use Illuminate\Database\Eloquent\Model;

class AfisAiReportCache extends Model
{
    public $timestamps  = false;
    protected $table    = 'afis_ai_report_cache';
    protected $fillable = [
        'cache_key', 'client_id', 'tracker_id', 'report_type',
        'response', 'engine_used', 'generated_at', 'expires_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];
}