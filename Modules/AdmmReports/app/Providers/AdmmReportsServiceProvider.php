<?php

namespace Modules\AdmmReports\Providers;

use Illuminate\Support\ServiceProvider;

class AdmmReportsServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'AdmmReports';
    protected string $moduleNameLower = 'admmreports';

    public function boot(): void
    {
        $this->registerViews();
        
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(
            module_path($this->moduleName, 'resources/views'),
            $this->moduleNameLower
        );
    }
}