<?php

namespace Modules\AfisIncidents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AdmmInventory\Models\Client;
use Modules\AfisEngine\Models\AfisAiReport;
use Modules\AfisPipeline\Models\AfisTracker;
use App\Models\User;

class AfisIncident extends Model
{
    protected $table    = 'afis_incidents';
    protected $fillable = [
        'client_id', 'tracker_id', 'navixy_tracker_id', 'vehicle_label',
        'incident_date', 'description', 'severity', 'status',
        'ai_report_id', 'logged_by',
        'report_path', 'prepared_by_name', 'prepared_by_title',
        'reviewed_by_name', 'reviewed_by_title',
    ];

    protected $casts = [
        'incident_date' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function tracker(): BelongsTo
    {
        return $this->belongsTo(AfisTracker::class);
    }

    public function aiReport(): BelongsTo
    {
        return $this->belongsTo(AfisAiReport::class);
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'minor'    => 'bg-blue-100 text-blue-700',
            'moderate' => 'bg-yellow-100 text-yellow-700',
            'serious'  => 'bg-orange-100 text-orange-700',
            'critical' => 'bg-red-100 text-red-700',
            default    => 'bg-gray-100 text-gray-600',
        };
    }
}