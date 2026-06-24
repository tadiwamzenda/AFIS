<?php

namespace Modules\AfisEngine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\AdmmInventory\Models\Client;
use App\Models\User;

class AfisAiReport extends Model
{
    protected $table    = 'afis_ai_reports';
    protected $fillable = [
        'client_id', 'tracker_id', 'report_type', 'engine_used',
        'prompt_used', 'response', 'tokens_used', 'duration_ms',
        'status', 'error_message', 'generated_by',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('report_type', $type);
    }
}