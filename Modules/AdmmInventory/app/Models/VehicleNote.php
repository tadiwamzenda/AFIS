<?php

namespace Modules\AdmmInventory\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A status note on a monitored vehicle (e.g. "Garage repairs, back Friday").
 * An ACTIVE note is unresolved and not past its expiry date; offline vehicles
 * with an active note can be hidden on the Offline States page.
 */
class VehicleNote extends Model
{
    protected $table    = 'afis_vehicle_notes';
    protected $fillable = ['tracker_id', 'client_id', 'tag', 'note', 'expires_at', 'created_by', 'resolved_at', 'resolved_by'];
    protected $casts    = ['expires_at' => 'date', 'resolved_at' => 'datetime'];

    const TAGS = [
        'garage_repairs' => 'Garage / repairs',
        'accident'       => 'Accident damage',
        'parked'         => 'Parked / not in use',
        'awaiting_parts' => 'Awaiting parts',
        'client_action'  => 'Client action pending',
        'other'          => 'Other',
    ];

    public function scopeActive(Builder $q): Builder
    {
        $today = now()->timezone('Africa/Harare')->toDateString();

        return $q->whereNull('resolved_at')
            ->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>=', $today));
    }

    public function getTagLabelAttribute(): string
    {
        return self::TAGS[$this->tag] ?? ucfirst(str_replace('_', ' ', $this->tag));
    }
}
