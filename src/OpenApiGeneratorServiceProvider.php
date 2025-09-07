<?php

namespace Pjadanowski\OpenApiGenerator;

use Illuminate\Support\ServiceProvider;
use Pjadanowski\OpenApiGenerator\Commands\GenerateOpenApiCommand;
use Pjadanowski\OpenApiGenerator\Services\ControllerAnalyzer;
use Pjadanowski\OpenApiGenerator\Services\DataClassAnalyzer;
use Pjadanowski\OpenApiGenerator\Services\FormRequestAnalyzer;
use Pjadanowski\OpenApiGenerator\Services\ResourceAnalyzer;
use Pjadanowski\OpenApiGenerator\Services\ResourceDiscoveryService;

class OpenApiGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ResourceDiscoveryService::class);
        $this->app->singleton(ControllerAnalyzer::class);
        $this->app->singleton(DataClassAnalyzer::class);
        $this->app->singleton(FormRequestAnalyzer::class);

        $this->app->singleton(ResourceAnalyzer::class, function ($app) {
            return new ResourceAnalyzer(
                $app->make(DataClassAnalyzer::class),
                $app->make(ResourceDiscoveryService::class)
            );
        });

        $this->app->singleton(RouteBasedOpenApiGenerator::class, function ($app) {
            return new RouteBasedOpenApiGenerator(
                $app->make(ControllerAnalyzer::class),
                $app->make(DataClassAnalyzer::class),
                $app->make(FormRequestAnalyzer::class),
                $app->make(ResourceAnalyzer::class),
                $app->make(ResourceDiscoveryService::class),
                $app->make(\Illuminate\Routing\Router::class)
            );
        });

        $this->mergeConfigFrom(
            __DIR__ . '/../config/openapi-generator.php',
            'openapi-generator'
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateOpenApiCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/openapi-generator.php' => config_path('openapi-generator.php'),
            ], 'openapi-generator-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/openapi-generator'),
            ], 'openapi-generator-views');
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'openapi-generator');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
