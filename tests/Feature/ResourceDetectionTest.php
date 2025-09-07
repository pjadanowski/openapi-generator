<?php

namespace Pjadanowski\OpenApiGenerator\Tests\Feature;

use Pjadanowski\OpenApiGenerator\Tests\TestCase;
use Pjadanowski\OpenApiGenerator\RouteBasedOpenApiGenerator;
use PHPUnit\Framework\Attributes\Test;

class ResourceDetectionTest extends TestCase
{
    #[Test]
    public function it_can_generate_openapi_spec()
    {
        $generator = app(RouteBasedOpenApiGenerator::class);
        $openApi = $generator->generateFromRoutes([
            'title' => 'Test API',
            'version' => '1.0.0'
        ]);

        $this->assertNotNull($openApi);
        $this->assertEquals('3.0.3', $openApi->openapi);
        $this->assertEquals('Test API', $openApi->info->title);
        $this->assertEquals('1.0.0', $openApi->info->version);
        $this->assertNotNull($openApi->components);
        $this->assertNotNull($openApi->paths);
    }

    #[Test]
    public function it_contains_required_components()
    {
        $generator = app(RouteBasedOpenApiGenerator::class);
        $openApi = $generator->generateFromRoutes();

        $this->assertNotNull($openApi->components);
        $this->assertNotNull($openApi->components->schemas);
        $this->assertIsArray($openApi->components->schemas);
    }

    #[Test]
    public function it_generates_valid_openapi_structure()
    {
        $generator = app(RouteBasedOpenApiGenerator::class);
        $openApi = $generator->generateFromRoutes();

        // Convert to array to verify structure
        $spec = json_decode(json_encode($openApi->getSerializableData()), true);

        $this->assertArrayHasKey('openapi', $spec);
        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('paths', $spec);
        $this->assertArrayHasKey('components', $spec);

        $this->assertArrayHasKey('title', $spec['info']);
        $this->assertArrayHasKey('version', $spec['info']);

        $this->assertArrayHasKey('schemas', $spec['components']);
    }
}
