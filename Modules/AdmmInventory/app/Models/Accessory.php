<?php

namespace Modules\AdmmInventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Accessory extends Model
{
    protected $table = 'adm_accessories';

    const STATUS_INSTALLED      = 'installed_client';
    const STATUS_OFFICE_STOCK   = 'in_office_stock';
    const STATUS_FAULTY         = 'faulty';
    const STATUS_DECOMMISSIONED = 'decommissioned';
    const STATUS_LOST           = 'lost';

    const CONTEXT_CLIENT   = 'client_assigned';
    const CONTEXT_INTERNAL = 'internal_stock';
    const CONTEXT_NONE     = 'unallocated';

    protected $fillable = [
        'serial_number', 'accessory_type_id', 'status',
        'location_context', 'client_id', 'gps_device_id',
        'purchase_date', 'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
    ];

    public function accessoryType(): BelongsTo
    {
        return $this->belongsTo(AccessoryType::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function gpsDevice(): BelongsTo
    {
        return $this->belongsTo(GpsDevice::class);
    }

    public function scopeInternalStock($query)
    {
        return $query->where('location_context', self::CONTEXT_INTERNAL);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_INSTALLED      => 'Installed — Client',
            self::STATUS_OFFICE_STOCK   => 'In Office Stock',
            self::STATUS_FAULTY         => 'Faulty',
            self::STATUS_DECOMMISSIONED => 'Decommissioned',
            self::STATUS_LOST           => 'Lost',
            default                     => ucfirst($this->status),
        };
    }
}