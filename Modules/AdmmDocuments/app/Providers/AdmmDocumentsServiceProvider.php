<?php

namespace Modules\AdmmDocuments\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\AdmmDocuments\Livewire\SimCardImport;

class AdmmDocumentsServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'AdmmDocuments';
    protected string $moduleNameLower = 'admmdocuments';

    public function boot(): void
    {
        $this->registerViews();

        $this->loadMigrationsFrom(
            module_path($this->moduleName, 'database/migrations')
        );

        Livewire::component(
            'admm-sim-import',
            SimCardImport::class
        );
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