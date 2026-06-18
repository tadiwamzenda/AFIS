<?php

namespace Modules\AuditLog\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Modules\AuditLog\Models\AuditLog;
use Modules\Core\Contracts\AuditLogInterface;

class AuditLogService implements AuditLogInterface
{
    public function record(
        string  $event,
        string  $module,
        array   $data       = [],
        ?int    $userId     = null,
        ?string $entityType = null,
        ?int    $entityId   = null
    ): void {
        try {
            AuditLog::create([
                'event'       => $event,
                'module'      => $module,
                'data'        => empty($data) ? null : $data,
                'user_id'     => $userId ?? Auth::id(),
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'ip_address'  => Request::ip(),
                'user_agent'  => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Never let audit logging crash the application
            Log::error('AuditLog: failed to record event', [
                'event'  => $event,
                'module' => $module,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}