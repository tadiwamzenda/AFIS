<?php

namespace Modules\AfisIncidents\Providers;

use Illuminate\Support\ServiceProvider;

class AfisIncidentsServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'AfisIncidents';
    protected string $moduleNameLower = 'afisincidents';

    public function boot(): void
    {
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        \Livewire\Livewire::component('afis-incident-form',    \Modules\AfisIncidents\Livewire\IncidentForm::class);
        \Livewire\Livewire::component('afis-incident-archive', \Modules\AfisIncidents\Livewire\IncidentArchive::class);
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