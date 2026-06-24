<?php

namespace Modules\AfisEngine\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AfisEngine\Services\AfisEngineService;
use Modules\AfisEngine\Services\Engines\ClaudeEngine;
use Modules\AfisEngine\Services\Engines\OpenAiEngine;
use Modules\Core\Contracts\AiEngineInterface;

class AfisEngineServiceProvider extends ServiceProvider
{
    protected string $moduleName      = 'AfisEngine';
    protected string $moduleNameLower = 'afisengine';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
    }

    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Bind engines as singletons
        $this->app->singleton(ClaudeEngine::class);
        $this->app->singleton(OpenAiEngine::class);

        // Bind AfisEngineService as the AiEngineInterface implementation
        $this->app->singleton(AiEngineInterface::class, AfisEngineService::class);
        $this->app->singleton(AfisEngineService::class, function ($app) {
            return new AfisEngineService(
                $app->make(ClaudeEngine::class),
                $app->make(OpenAiEngine::class),
            );
        });
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            'afisengine'
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