<?php

namespace Modules\AdmmInventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SimCard extends Model
{
    protected $table = 'adm_sim_cards';

    const STATUS_ACTIVE_CLIENT   = 'active_client';
    const STATUS_ACTIVE_INTERNAL = 'active_internal';
    const STATUS_UNASSIGNED      = 'unassigned';
    const STATUS_INACTIVE        = 'inactive';
    const STATUS_SUSPENDED       = 'suspended';
    const STATUS_DEACTIVATED     = 'deactivated';
    const STATUS_LOST            = 'lost';

    const CONTEXT_CLIENT   = 'client_assigned';
    const CONTEXT_INTERNAL = 'internal_stock';
    const CONTEXT_NONE     = 'unallocated';

    protected $fillable = [
        'iccid', 'msisdn', 'network_provider', 'bundle_type',
        'bundle_renewal_date', 'status', 'location_context',
        'client_id', 'navixy_tracker_id', 'notes',
    ];

    protected $casts = [
        'bundle_renewal_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function gpsDevice(): HasOne
    {
        return $this->hasOne(GpsDevice::class);
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

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeRenewalDueBefore($query, \Carbon\Carbon $date)
    {
        return $query->whereNotNull('bundle_renewal_date')
                     ->where('bundle_renewal_date', '<=', $date);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isClientAssigned(): bool
    {
        return $this->location_context === self::CONTEXT_CLIENT;
    }

    public function isInternalStock(): bool
    {
        return $this->location_context === self::CONTEXT_INTERNAL;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE_CLIENT   => 'Active — Client',
            self::STATUS_ACTIVE_INTERNAL => 'Active — Internal',
            self::STATUS_UNASSIGNED      => 'Unassigned',
            self::STATUS_INACTIVE        => 'Inactive',
            self::STATUS_SUSPENDED       => 'Suspended',
            self::STATUS_DEACTIVATED     => 'Deactivated',
            self::STATUS_LOST            => 'Lost',
            default                      => ucfirst($this->status),
        };
    }
}