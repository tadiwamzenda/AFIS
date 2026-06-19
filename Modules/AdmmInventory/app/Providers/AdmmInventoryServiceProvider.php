<?php

namespace Modules\AdmmInventory\Providers;

use Illuminate\Support\ServiceProvider;

class AdmmInventoryServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'AdmmInventory';
    protected string $moduleNameLower = 'admminventory';

    public function boot(): void
    {
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        \Livewire\Livewire::component('admm-sim-card-index', \Modules\AdmmInventory\Livewire\SimCards\SimCardIndex::class);
        \Livewire\Livewire::component('admm-sim-card-form',  \Modules\AdmmInventory\Livewire\SimCards\SimCardForm::class);
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