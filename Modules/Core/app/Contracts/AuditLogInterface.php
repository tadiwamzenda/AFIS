<?php

namespace Modules\Core\Contracts;

interface AuditLogInterface
{
    public function record(
        string  $event,
        string  $module,
        array   $data       = [],
        ?int    $userId     = null,
        ?string $entityType = null,
        ?int    $entityId   = null
    ): void;
}