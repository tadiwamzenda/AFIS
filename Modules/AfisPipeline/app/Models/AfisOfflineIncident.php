<?php

namespace Modules\AfisPipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AdmmInventory\Models\Client;
use Modules\Notifications\Models\AfisNotification;

class AfisOfflineIncident extends Model
{
    protected $table = 'afis_offline_incidents';

    protected $fillable = [
        'tracker_id', 'client_id', 'went_offline_at', 'came_online_at',
        'comment', 'resolution', 'notification_id',
        'action_party', 'triage_updated_at', 'triage_updated_by',
    ];

    protected $casts = [
        'went_offline_at'   => 'datetime',
        'came_online_at'    => 'datetime',
        'triage_updated_at' => 'datetime',
    ];

    /** Comment options, in dropdown order. */
    const COMMENTS = [
        'Accident (Damaged)',
        'Decommissioned (Inactive)',
        'Garage (Repairs)',
        'Parked (Inactive)',
        'Functional (Active)',
    ];

    /** Who has to act on the offline vehicle. */
    const ACTION_PARTIES = ['Bantu Track', 'Client'];

    /** Blank counts as these, so an un-triaged offline vehicle is still counted as offline. */
    const DEFAULT_COMMENT = 'Functional (Active)';
    const DEFAULT_PARTY   = 'Bantu Track';

    public function tracker(): BelongsTo
    {
        return $this->belongsTo(AfisTracker::class, 'tracker_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(AfisNotification::class, 'notification_id');
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('came_online_at');
    }

    /**
     * Incidents that should NOT count as offline: marked anything other than
     * "Functional (Active)", or with the Client as action party.
     */
    public function scopeExcluded($query)
    {
        return $query->where(function ($w) {
            $w->where(fn ($c) => $c->whereNotNull('comment')->where('comment', '!=', self::DEFAULT_COMMENT))
              ->orWhere(fn ($p) => $p->whereNotNull('action_party')->where('action_party', '!=', self::DEFAULT_PARTY));
        });
    }

    public function isOpen(): bool
    {
        return $this->came_online_at === null;
    }

    /** True when this offline vehicle is Functional (Active) and Bantu Track's to deal with. */
    public function isCounted(): bool
    {
        return ($this->comment ?: self::DEFAULT_COMMENT) === self::DEFAULT_COMMENT
            && ($this->action_party ?: self::DEFAULT_PARTY) === self::DEFAULT_PARTY;
    }
}
