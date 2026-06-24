<?php

namespace Modules\AfisPipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AdmmInventory\Models\Client;

class AfisSyncLog extends Model
{
    public $timestamps  = false;
    protected $table    = 'afis_sync_logs';
    protected $fillable = [
        'client_id', 'status', 'trackers_synced',
        'trips_synced', 'events_synced',
        'error_message', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}