<?php

namespace Modules\AuditLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class AuditLog extends Model
{
    // Immutable — no updates, no deletes
    public $timestamps  = false;
    public $incrementing = true;

    protected $fillable = [
        'event',
        'module',
        'data',
        'user_id',
        'entity_type',
        'entity_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'data'       => 'array',
        'created_at' => 'datetime',
    ];

    // Prevent updates — audit records are write-once
    public function save(array $options = []): bool
    {
        if (!$this->exists) {
            return parent::save($options);
        }
        return false;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeForEntity($query, string $type, int $id)
    {
        return $query->where('entity_type', $type)->where('entity_id', $id);
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }
}