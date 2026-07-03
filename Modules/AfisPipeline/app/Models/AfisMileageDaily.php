<?php

namespace Modules\AfisPipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AdmmInventory\Models\Client;

class AfisMileageDaily extends Model
{
    public $timestamps  = false;
    protected $table    = 'afis_mileage_daily';
    protected $fillable = [
        'tracker_id', 'client_id', 'navixy_tracker_id', 'date', 'mileage_km',
    ];

    protected $casts = ['date' => 'date'];

    public function tracker(): BelongsTo
    {
        return $this->belongsTo(AfisTracker::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}