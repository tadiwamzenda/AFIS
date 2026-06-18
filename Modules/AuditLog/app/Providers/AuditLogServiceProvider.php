<?php

namespace Modules\AuditLog\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AuditLog\Services\AuditLogService;
use Modules\Core\Contracts\AuditLogInterface;

class AuditLogServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'AuditLog';
    protected string $moduleNameLower = 'auditlog';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Bind the interface as a singleton — stateless service
        $this->app->singleton(AuditLogInterface::class, AuditLogService::class);
    }
}