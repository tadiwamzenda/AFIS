<?php

namespace Modules\AdmmInventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GpsDevice extends Model
{
    protected $table    = 'adm_gps_devices';
    protected $fillable = [
    'imei', 'model', 'device_type',
    'vehicle_registration', 'vehicle_make', 'fleet_number',
    'status', 'client_id', 'sim_card_id',
    'technician', 'installed_at', 'notes',
    ];

    protected $casts = [
        'installed_at' => 'date',
    ];

    const DEVICE_TYPES = [
        'MT100', 'MT 100', 'VT100', 'VT 100', 'VT100-L',
        'VT200', 'VT 200', 'GT06N', 'FMC920', 'FMB140', 'Other',
    ];

    const STATUSES = [
        'in_office_stock' => 'In Office Stock',
        'installed'       => 'Installed',
        'decommissioned'  => 'Decommissioned',
        'lost_stolen'     => 'Lost / Stolen',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function simCard(): BelongsTo
    {
        return $this->belongsTo(SimCard::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['decommissioned', 'lost_stolen']);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}