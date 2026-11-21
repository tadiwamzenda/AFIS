<?php

namespace Modules\AdmmInventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallationEvent extends Model
{
    protected $table      = 'adm_installation_events';
    public    $timestamps = false; // created_at is set in booted() below

    protected $fillable = [
        'gps_device_id', 'sim_card_id', 'event_type', 'client_id',
        'vehicle_registration', 'fleet_number', 'vehicle_make',
        'installation_location', 'technician_id', 'performed_by', 'notes',
    ];

    protected $casts = ['created_at' => 'datetime'];

    const TYPES = [
        'paired', 'unpaired', 'assigned', 'installed', 'repair_swap',
        'uncoupled', 'installer_changed', 'edited',
        'verified', 'flagged_lost', 'marked_faulty', 'returned_to_stock', 'written_off',
    ];

    protected static function booted(): void
    {
        // Use the app's own clock, not MySQL's, so event times match the rest of AFIS
        static::creating(function (self $event) {
            $event->created_at ??= now();
        });
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(GpsDevice::class, 'gps_device_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}