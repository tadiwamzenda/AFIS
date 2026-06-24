<?php

namespace Modules\AfisPipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AdmmInventory\Models\Client;

class AfisTracker extends Model
{
    protected $table    = 'afis_trackers';
    protected $fillable = [
        'client_id', 'navixy_tracker_id', 'label',
        'model_name', 'vehicle_registration', 'is_active',
        'last_active_at', 'last_lat', 'last_lng', 'last_synced_at',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_active_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(AfisTrip::class, 'tracker_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AfisEvent::class, 'tracker_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }
}