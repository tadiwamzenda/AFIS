<?php

namespace Modules\AdmmInventory\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AdmmInventory\Console\TakeStockSnapshotCommand;
use Modules\AdmmInventory\Console\Commands\EncryptLegacyApiKeysCommand;
use Illuminate\Console\Scheduling\Schedule;


class AdmmInventoryServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'AdmmInventory';
    protected string $moduleNameLower = 'admminventory';

    public function boot(): void
    {
        \Livewire\Livewire::component('admm-asset-register', \Modules\AdmmInventory\Livewire\AssetRegister\AssetRegister::class);
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        $this->commands([
            TakeStockSnapshotCommand::class,
            EncryptLegacyApiKeysCommand::class,
        ]);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('admm:snapshot')->dailyAt('23:55');
        });

        \Livewire\Livewire::component('admm-sim-card-index', \Modules\AdmmInventory\Livewire\SimCards\SimCardIndex::class);
        \Livewire\Livewire::component('admm-sim-card-form',  \Modules\AdmmInventory\Livewire\SimCards\SimCardForm::class);
        \Livewire\Livewire::component('admm-client-index', \Modules\AdmmInventory\Livewire\Clients\ClientIndex::class);
        \Livewire\Livewire::component('admm-client-form',  \Modules\AdmmInventory\Livewire\Clients\ClientForm::class);
        \Livewire\Livewire::component('admm-gps-device-index', \Modules\AdmmInventory\Livewire\GpsDevices\GpsDeviceIndex::class);
        \Livewire\Livewire::component('admm-gps-device-form',  \Modules\AdmmInventory\Livewire\GpsDevices\GpsDeviceForm::class);
        \Livewire\Livewire::component('admm-accessory-index', \Modules\AdmmInventory\Livewire\Accessories\AccessoryIndex::class);
        \Livewire\Livewire::component('admm-accessory-form',  \Modules\AdmmInventory\Livewire\Accessories\AccessoryForm::class);
        \Livewire\Livewire::component('admm-asset-register', \Modules\AdmmInventory\Livewire\AssetRegister\AssetRegister::class);
        \Livewire\Livewire::component('admm-stock-management', \Modules\AdmmInventory\Livewire\StockManagement\StockManagement::class);
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