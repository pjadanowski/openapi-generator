<?php

namespace Pjadanowski\OpenApiGenerator\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Pjadanowski\OpenApiGenerator\OpenApiGeneratorServiceProvider;
use PHPUnit\Framework\Attributes\Test;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            OpenApiGeneratorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('openapi-generator', [
            'resource_paths' => [
                __DIR__ . '/Fixtures',  // Point to our test fixtures
            ],
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
            ],
            'output' => [
                'path' => 'test-openapi.json',
                'format' => 'json',
            ],
        ]);

        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set up basic app config
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
    }
}
