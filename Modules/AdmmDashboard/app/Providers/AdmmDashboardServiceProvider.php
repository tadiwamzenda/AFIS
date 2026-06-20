<?php

namespace Modules\AdmmDashboard\Providers;

use Illuminate\Support\ServiceProvider;

class AdmmDashboardServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'AdmmDashboard';
    protected string $moduleNameLower = 'admmdashboard';

    public function boot(): void
    {
        $this->registerViews();

        \Livewire\Livewire::component('admm-dashboard', \Modules\AdmmDashboard\Livewire\AdminDashboard::class);
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