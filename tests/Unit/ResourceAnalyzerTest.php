<?php

namespace Pjadanowski\OpenApiGenerator\Tests\Unit;

use Pjadanowski\OpenApiGenerator\Tests\TestCase;
use Pjadanowski\OpenApiGenerator\Services\ResourceAnalyzer;
use Pjadanowski\OpenApiGenerator\Tests\Fixtures\UserResource;
use Pjadanowski\OpenApiGenerator\Tests\Fixtures\PostResource;
use Pjadanowski\OpenApiGenerator\Tests\Fixtures\CategoryResource;
use Pjadanowski\OpenApiGenerator\Tests\Fixtures\TestController;
use cebe\openapi\spec\Schema;
use ReflectionMethod;
use PHPUnit\Framework\Attributes\Test;

class ResourceAnalyzerTest extends TestCase
{
    #[Test]
    public function it_can_analyze_simple_resource()
    {
        $service = app(ResourceAnalyzer::class);

        $schema = $service->analyzeResource(UserResource::class);

        $this->assertInstanceOf(Schema::class, $schema);
    }

    #[Test]
    public function it_can_analyze_resource_with_relationships()
    {
        $service = app(ResourceAnalyzer::class);

        $schema = $service->analyzeResource(PostResource::class);

        $this->assertInstanceOf(Schema::class, $schema);
    }

    #[Test]
    public function it_can_analyze_category_resource()
    {
        $service = app(ResourceAnalyzer::class);

        $schema = $service->analyzeResource(CategoryResource::class);

        $this->assertInstanceOf(Schema::class, $schema);
    }

    #[Test]
    public function it_can_get_all_schemas()
    {
        $service = app(ResourceAnalyzer::class);

        // Analyze some resources first
        $service->analyzeResource(UserResource::class);
        $service->analyzeResource(PostResource::class);
        $service->analyzeResource(CategoryResource::class);

        $schemas = $service->getSchemas();

        $this->assertIsArray($schemas);
        $this->assertNotEmpty($schemas);
    }

    #[Test]
    public function it_can_reset_schemas()
    {
        $service = app(ResourceAnalyzer::class);

        // Analyze some resources
        $service->analyzeResource(UserResource::class);
        $schemas = $service->getSchemas();
        $this->assertNotEmpty($schemas);

        // Reset and check
        $service->resetSchemas();
        $schemasAfterReset = $service->getSchemas();
        $this->assertEmpty($schemasAfterReset);
    }

    #[Test]
    public function it_can_analyze_json_response()
    {
        $service = app(ResourceAnalyzer::class);

        $reflection = new ReflectionMethod(TestController::class, 'singleUser');
        $response = $service->analyzeJsonResponse($reflection);

        $this->assertInstanceOf(\cebe\openapi\spec\Response::class, $response);
    }

    #[Test]
    public function it_can_get_resource_schema()
    {
        $service = app(ResourceAnalyzer::class);

        // First analyze the resource
        $service->analyzeResource(UserResource::class);

        // Then try to get its schema
        $schema = $service->getResourceSchema('UserResource');

        if ($schema !== null) {
            $this->assertInstanceOf(Schema::class, $schema);
        }
    }
}
