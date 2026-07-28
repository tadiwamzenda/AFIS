<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AfisNotification extends Model
{
    public $timestamps  = false;
    protected $table    = 'afis_notifications';

    protected $fillable = [
        'type', 'title', 'message', 'severity', 'module',
        'notifiable_type', 'notifiable_id',
        'user_id', 'data', 'read_at', 'email_sent',
    ];

    protected $casts = [
        'data'       => 'array',
        'read_at'    => 'datetime',
        'created_at' => 'datetime',
        'email_sent' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable()
    {
        return $this->morphTo();
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'border-red-200 bg-red-50 text-red-700',
            'severe'   => 'border-orange-200 bg-orange-50 text-orange-700',
            'warning'  => 'border-yellow-200 bg-yellow-50 text-yellow-700',
            default    => 'border-blue-200 bg-blue-50 text-blue-700',
        };
    }

    

    public function getSeverityIconAttribute(): string
    {
        return match($this->severity) {
            'critical' => '🔴',
            'severe'   => '🟠',
            'warning'  => '🟡',
            default    => '🔵',
        };
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}