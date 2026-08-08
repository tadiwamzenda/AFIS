<?php

namespace Modules\Notifications\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Notifications\Console\CheckAlertsCommand;
use Modules\Notifications\Services\NotificationService;

class NotificationsServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'Notifications';
    protected string $moduleNameLower = 'notifications';

    public function boot(): void
    {
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->commands([CheckAlertsCommand::class]);

        // Run alert checks every 30 minutes
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('afis:check-alerts')->cron('10,40 * * * *');
        });

        \Livewire\Livewire::component('afis-notification-bell', \Modules\Notifications\Livewire\NotificationBell::class);
        \Livewire\Livewire::component('notifications-centre', \Modules\Notifications\Livewire\NotificationCentre::class);
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Bind as singleton — stateless service
        $this->app->singleton(NotificationService::class);
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(
            module_path($this->moduleName, 'resources/views'),
            $this->moduleNameLower
        );
    }
}