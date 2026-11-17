<?php

namespace Modules\AdmmInventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallationEvent extends Model
{
    protected $table      = 'adm_installation_events';
    public    $timestamps = false; // created_at is set by the database

    protected $fillable = [
        'gps_device_id', 'sim_card_id', 'event_type', 'client_id',
        'vehicle_registration', 'fleet_number', 'vehicle_make',
        'installation_location', 'technician_id', 'performed_by', 'notes',
    ];

    protected $casts = ['created_at' => 'datetime'];

    const TYPES = [
        'paired', 'assigned', 'installed', 'repair_swap',
        'uncoupled', 'installer_changed', 'edited',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(GpsDevice::class, 'gps_device_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
