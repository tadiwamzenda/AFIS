<?php

namespace Modules\AfisPipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\AdmmInventory\Models\Client;

class AfisTrackerGroup extends Model
{
    protected $table    = 'afis_tracker_groups';
    protected $fillable = [
        'navixy_group_id', 'navixy_instance', 'title', 'color', 'client_id',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function trackers(): HasMany
    {
        return $this->hasMany(AfisTracker::class, 'navixy_group_id', 'navixy_group_id');
    }
}