<?php

namespace Modules\AfisPipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AdmmInventory\Models\Client;

class AfisTrip extends Model
{
    public $timestamps  = false;
    protected $table    = 'afis_trips';
    protected $fillable = [
        'tracker_id', 'client_id', 'navixy_tracker_id',
        'start_time', 'end_time', 'distance_km',
        'avg_speed_kmh', 'max_speed_kmh',
        'duration_minutes', 'stops_count',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
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