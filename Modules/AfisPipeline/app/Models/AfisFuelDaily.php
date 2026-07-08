<?php

namespace Modules\AfisPipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AdmmInventory\Models\Client;

class AfisFuelDaily extends Model
{
    public $timestamps  = false;
    protected $table    = 'afis_fuel_daily';
    protected $fillable = [
        'tracker_id', 'client_id', 'navixy_tracker_id', 'date',
        'mileage_km', 'refuel_count', 'volume_litres', 'consumed_litres',
        'consumption_km_per_litre', 'has_drain', 'drain_litres', 'vehicle_label',
    ];

    protected $casts = [
        'date'      => 'date',
        'has_drain' => 'boolean',
    ];

    public function tracker(): BelongsTo { return $this->belongsTo(AfisTracker::class); }
    public function client(): BelongsTo  { return $this->belongsTo(Client::class); }
}