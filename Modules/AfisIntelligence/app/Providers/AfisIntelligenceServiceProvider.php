<?php

namespace Modules\AfisIntelligence\Providers;

use Illuminate\Support\ServiceProvider;

class AfisIntelligenceServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'AfisIntelligence';
    protected string $moduleNameLower = 'afisintelligence';

    public function boot(): void
    {
        $this->registerViews();

        \Livewire\Livewire::component('afis-intelligence-dashboard', \Modules\AfisIntelligence\Livewire\IntelligenceDashboard::class);
        \Livewire\Livewire::component('afis-intelligence-archive',   \Modules\AfisIntelligence\Livewire\IntelligenceArchive::class);
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