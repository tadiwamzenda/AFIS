<?php

namespace Modules\AfisPipeline\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\AfisPipeline\Console\SyncFleetCommand;

class AfisPipelineServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'AfisPipeline';
    protected string $moduleNameLower = 'afispipeline';

    public function boot(): void
    {
        $this->registerViews();
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->commands([SyncFleetCommand::class]);

        // Register scheduler — runs every 15 minutes in production
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('afis:sync-fleet')->everyFifteenMinutes();
        });

        \Livewire\Livewire::component('afis-pipeline-dashboard', \Modules\AfisPipeline\Livewire\SyncDashboard::class);
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(module_path($this->moduleName, 'resources/views'), $this->moduleNameLower);
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/config.php'), 'afispipeline');
    }
}