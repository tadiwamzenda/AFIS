<?php

namespace Modules\AfisPipeline\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\AfisPipeline\Console\BackfillTripsCommand;
use Modules\AfisPipeline\Console\SyncFleetCommand;
use Modules\AfisPipeline\Console\SyncTrackerGroupsCommand;

class AfisPipelineServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'AfisPipeline';
    protected string $moduleNameLower = 'afispipeline';

    public function boot(): void
    {
        $this->registerViews();
        $this->registerConfig();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->commands([
            SyncFleetCommand::class,
            SyncTrackerGroupsCommand::class,
            BackfillTripsCommand::class,
        ]);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('afis:sync-fleet')->everyFifteenMinutes();
            $schedule->command('afis:sync-groups')->daily();
        });

        \Livewire\Livewire::component('afis-pipeline-dashboard', \Modules\AfisPipeline\Livewire\SyncDashboard::class);
    }


    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
        $this->app->singleton(\Modules\AfisPipeline\Services\FuelDataParser::class);
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