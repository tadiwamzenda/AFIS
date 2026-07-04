<?php

namespace Modules\AfisPipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AdmmInventory\Models\Client;

class AfisFuelEvent extends Model
{
    public $timestamps  = false;
    protected $table    = 'afis_fuel_events';
    protected $fillable = [
        'tracker_id', 'client_id', 'navixy_tracker_id',
        'event_type', 'volume_litres', 'initial_volume', 'final_volume',
        'mileage_at_event', 'occurred_at', 'lat', 'lng', 'address',
    ];

    protected $casts = ['occurred_at' => 'datetime'];

    public function tracker(): BelongsTo { return $this->belongsTo(AfisTracker::class); }
    public function client(): BelongsTo  { return $this->belongsTo(Client::class); }
}