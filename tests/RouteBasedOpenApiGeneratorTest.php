<?php

namespace Pjadanowski\OpenApiGenerator\Tests;

use Pjadanowski\OpenApiGenerator\RouteBasedOpenApiGenerator;
use Pjadanowski\OpenApiGenerator\Tests\Fixtures\TestController;
use PHPUnit\Framework\Attributes\Test;

class RouteBasedOpenApiGeneratorTest extends TestCase
{
    #[Test]
    public function test_generates_openapi_from_routes(): void
    {
        // Register test routes
        app('router')->get('api/users', [TestController::class, 'index']);
        app('router')->post('api/users', [TestController::class, 'store']);
        app('router')->get('api/users/{id}', [TestController::class, 'show']);

        $generator = app(RouteBasedOpenApiGenerator::class);

        $openApi = $generator->generateFromRoutes([
            'title' => 'Test API',
            'version' => '1.0.0',
        ]);

        $this->assertEquals('3.0.3', $openApi->openapi); // Fixed expected version
        $this->assertEquals('Test API', $openApi->info->title);
        $this->assertEquals('1.0.0', $openApi->info->version);

        // Should have paths for the routes
        $this->assertNotEmpty($openApi->paths);
    }

    #[Test]
    public function test_processes_controller_methods(): void
    {
        app('router')->get('api/users', [TestController::class, 'index']);

        $generator = app(RouteBasedOpenApiGenerator::class);
        $openApi = $generator->generateFromRoutes();

        // Should process the route (check if paths exist)
        $this->assertNotEmpty($openApi->paths);
    }
}
