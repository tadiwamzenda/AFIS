<?php

namespace Modules\AfisPipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AdmmInventory\Models\Client;

class AfisEvent extends Model
{
    public $timestamps  = false;
    protected $table    = 'afis_events';
    protected $fillable = [
        'tracker_id', 'client_id', 'navixy_tracker_id',
        'event_type', 'occurred_at', 'lat', 'lng', 'extra_data',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'extra_data'  => 'array',
    ];

    public function tracker(): BelongsTo
    {
        return $this->belongsTo(AfisTracker::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}