<?php

namespace Modules\AdmmWorkflows\Providers;

use Illuminate\Support\ServiceProvider;

class AdmmWorkflowsServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'AdmmWorkflows';
    protected string $moduleNameLower = 'admmworkflows';

    public function boot(): void
    {
        $this->registerViews();

        \Livewire\Livewire::component('admm-sim-swap',      \Modules\AdmmWorkflows\Livewire\SimSwapWizard::class);
        \Livewire\Livewire::component('admm-device-install',\Modules\AdmmWorkflows\Livewire\DeviceInstallWizard::class);
        \Livewire\Livewire::component('admm-device-remove', \Modules\AdmmWorkflows\Livewire\DeviceRemoveWizard::class);
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