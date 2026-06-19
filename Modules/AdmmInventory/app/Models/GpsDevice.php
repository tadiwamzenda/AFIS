<?php

namespace Modules\AdmmInventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GpsDevice extends Model
{
    protected $table = 'adm_gps_devices';

    const STATUS_INSTALLED      = 'installed_client';
    const STATUS_OFFICE_STOCK   = 'in_office_stock';
    const STATUS_UNDER_REPAIR   = 'under_repair';
    const STATUS_AWAITING_DISP  = 'awaiting_disposal';
    const STATUS_DECOMMISSIONED = 'decommissioned';
    const STATUS_LOST           = 'lost_stolen';

    const CONTEXT_CLIENT   = 'client_assigned';
    const CONTEXT_INTERNAL = 'internal_stock';
    const CONTEXT_NONE     = 'unallocated';

    protected $fillable = [
        'serial_number', 'model', 'firmware_version', 'purchase_date',
        'warranty_expiry_date', 'status', 'location_context',
        'client_id', 'sim_card_id', 'navixy_tracker_id',
        'vehicle_registration', 'installed_at', 'notes',
    ];

    protected $casts = [
        'purchase_date'        => 'date',
        'warranty_expiry_date' => 'date',
        'installed_at'         => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function simCard(): BelongsTo
    {
        return $this->belongsTo(SimCard::class);
    }

    public function accessories(): HasMany
    {
        return $this->hasMany(Accessory::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeClientAssigned($query)
    {
        return $query->where('location_context', self::CONTEXT_CLIENT);
    }

    public function scopeInternalStock($query)
    {
        return $query->where('location_context', self::CONTEXT_INTERNAL);
    }

    public function scopeWarrantyExpiresBefore($query, \Carbon\Carbon $date)
    {
        return $query->whereNotNull('warranty_expiry_date')
                     ->where('warranty_expiry_date', '<=', $date);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isClientAssigned(): bool
    {
        return $this->location_context === self::CONTEXT_CLIENT;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_INSTALLED      => 'Installed — Client',
            self::STATUS_OFFICE_STOCK   => 'In Office Stock',
            self::STATUS_UNDER_REPAIR   => 'Under Repair',
            self::STATUS_AWAITING_DISP  => 'Awaiting Disposal',
            self::STATUS_DECOMMISSIONED => 'Decommissioned',
            self::STATUS_LOST           => 'Lost / Stolen',
            default                     => ucfirst($this->status),
        };
    }
}