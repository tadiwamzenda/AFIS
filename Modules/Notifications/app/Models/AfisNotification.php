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

    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'warning'  => 'bg-yellow-100 text-yellow-700 border-yellow-200',
            'critical' => 'bg-red-100 text-red-700 border-red-200',
            default    => 'bg-blue-100 text-blue-700 border-blue-200',
        };
    }

    public function getSeverityIconAttribute(): string
    {
        return match($this->severity) {
            'warning'  => '⚠',
            'critical' => '🔴',
            default    => 'ℹ',
        };
    }
}