<?php

namespace Modules\NavixyClient\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\NavixyClientInterface;
use Modules\NavixyClient\Services\NavixyClientService;

class NavixyClientServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'NavixyClient';
    protected string $moduleNameLower = 'navixyclient';

    public function boot(): void
    {
        $this->registerConfig();
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Bind the interface — request-scoped so hash is always fresh per request
        $this->app->scoped(NavixyClientInterface::class, NavixyClientService::class);
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );
    }
}