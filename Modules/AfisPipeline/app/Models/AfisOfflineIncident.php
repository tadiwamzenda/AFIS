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
    ];

    protected $casts = [
        'went_offline_at' => 'datetime',
        'came_online_at'  => 'datetime',
    ];

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

    public function isOpen(): bool
    {
        return $this->came_online_at === null;
    }
}