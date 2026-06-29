<?php

namespace Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Modules\Auth\Http\Middleware\NavixyHashRefreshMiddleware;

class AuthServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'Auth';
    protected string $moduleNameLower = 'auth';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();

        // Register hash refresh middleware on the web group
        $router = $this->app->make(Router::class);
        $router->pushMiddlewareToGroup('web', NavixyHashRefreshMiddleware::class);
        $router->aliasMiddleware('navixy.hash', NavixyHashRefreshMiddleware::class);
        \Livewire\Livewire::component('auth-bt-user-manager', \Modules\Auth\Livewire\BtUserManager::class);
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            'auth-module'
        );
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(
            module_path($this->moduleName, 'resources/views'),
            $this->moduleNameLower
        );
    }
}