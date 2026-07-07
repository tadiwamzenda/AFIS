<?php

namespace Modules\AfisPipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AdmmInventory\Models\Client;

class AfisFuelReading extends Model
{
    public $timestamps  = false;
    protected $table    = 'afis_fuel_readings';
    protected $fillable = [
        'tracker_id', 'client_id', 'navixy_tracker_id',
        'sensor_id', 'value_litres', 'reading_time',
    ];

    protected $casts = ['reading_time' => 'datetime'];

    public function tracker(): BelongsTo { return $this->belongsTo(AfisTracker::class); }
    public function client(): BelongsTo  { return $this->belongsTo(Client::class); }
}