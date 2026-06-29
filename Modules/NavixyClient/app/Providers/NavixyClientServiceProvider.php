<?php

namespace Modules\NavixyClient\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Contracts\NavixyClientInterface;
use Modules\NavixyClient\Console\SyncNavixyUsersCommand;
use Modules\NavixyClient\Services\NavixyClientService;

class NavixyClientServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'NavixyClient';
    protected string $moduleNameLower = 'navixyclient';

    public function boot(): void
    {
        $this->registerConfig();
        $this->commands([SyncNavixyUsersCommand::class]);

        // Sync users from both Navixy instances every hour
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('navixy:sync-users')->hourly();
        });
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
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