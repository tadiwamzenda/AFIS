<?php

namespace Modules\AfisPipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AdmmInventory\Models\Client;

class AfisDeviceAlert extends Model
{
    public $timestamps  = false;
    protected $table    = 'afis_device_alerts';
    protected $fillable = [
        'tracker_id', 'client_id', 'navixy_tracker_id',
        'navixy_alert_id', 'event_type', 'message', 'is_read',
        'occurred_at', 'lat', 'lng', 'address', 'extra_data',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'is_read'     => 'boolean',
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

    // Alert types that represent actual driver behaviour events
    const DRIVER_EVENTS = [
        'speeding', 'harsh_braking', 'harsh_acceleration',
        'cornering', 'idling', 'sos', 'geofence_in', 'geofence_out',
    ];

    // Alert types that are device/system events
    const DEVICE_EVENTS = [
        'lowpower', 'battery_off', 'battery_on', 'gps_lost',
        'connection_lost', 'connection_restored',
    ];
}