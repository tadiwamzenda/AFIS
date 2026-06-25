<?php

namespace Modules\AfisPortal\Providers;

use Illuminate\Support\ServiceProvider;

class AfisPortalServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'AfisPortal';
    protected string $moduleNameLower = 'afisportal';

    public function boot(): void
    {
        $this->registerViews();

        \Livewire\Livewire::component('afis-fleet-overview',         \Modules\AfisPortal\Livewire\FleetOverview::class);
        \Livewire\Livewire::component('afis-client-fleet-dashboard', \Modules\AfisPortal\Livewire\ClientFleetDashboard::class);
        \Livewire\Livewire::component('afis-vehicle-inspector',      \Modules\AfisPortal\Livewire\VehicleInspector::class);
        \Livewire\Livewire::component('afis-report-viewer',          \Modules\AfisPortal\Livewire\ReportViewer::class);
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