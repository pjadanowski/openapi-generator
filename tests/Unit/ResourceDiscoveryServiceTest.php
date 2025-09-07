<?php

namespace Pjadanowski\OpenApiGenerator\Tests\Unit;

use Pjadanowski\OpenApiGenerator\Tests\TestCase;
use Pjadanowski\OpenApiGenerator\Services\ResourceDiscoveryService;
use Pjadanowski\OpenApiGenerator\Tests\Fixtures\UserResource;
use Pjadanowski\OpenApiGenerator\Tests\Fixtures\PostResource;
use Pjadanowski\OpenApiGenerator\Tests\Fixtures\CategoryResource;
use PHPUnit\Framework\Attributes\Test;

class ResourceDiscoveryServiceTest extends TestCase
{
    #[Test]
    public function it_can_discover_all_resource_classes()
    {
        $service = app(ResourceDiscoveryService::class);
        $resources = $service->discoverResources();

        $this->assertIsArray($resources);
        $this->assertNotEmpty($resources);

        // Should contain our fixture resources
        $resourceNames = array_map('class_basename', array_keys($resources));
        $this->assertContains('UserResource', $resourceNames);
        $this->assertContains('PostResource', $resourceNames);
        $this->assertContains('CategoryResource', $resourceNames);
    }

    #[Test]
    public function it_can_find_resource_by_name()
    {
        $service = app(ResourceDiscoveryService::class);

        $userResource = $service->findResourceByName('UserResource');
        $this->assertNotNull($userResource);
        $this->assertTrue(str_contains($userResource, 'UserResource'));

        $postResource = $service->findResourceByName('PostResource');
        $this->assertNotNull($postResource);
        $this->assertTrue(str_contains($postResource, 'PostResource'));

        $categoryResource = $service->findResourceByName('CategoryResource');
        $this->assertNotNull($categoryResource);
        $this->assertTrue(str_contains($categoryResource, 'CategoryResource'));
    }

    #[Test]
    public function it_returns_null_for_non_existent_resource()
    {
        $service = app(ResourceDiscoveryService::class);

        $result = $service->findResourceByName('NonExistentResource');
        $this->assertNull($result);
    }

    #[Test]
    public function it_can_check_if_class_is_resource()
    {
        $service = app(ResourceDiscoveryService::class);

        $this->assertTrue($service->isResourceClass(UserResource::class));
        $this->assertTrue($service->isResourceClass(PostResource::class));
        $this->assertTrue($service->isResourceClass(CategoryResource::class));

        $this->assertFalse($service->isResourceClass('stdClass'));
        $this->assertFalse($service->isResourceClass('NonExistentClass'));
    }

    #[Test]
    public function it_caches_discovered_resources()
    {
        $service = app(ResourceDiscoveryService::class);

        // First call
        $resources1 = $service->discoverResources();

        // Second call should return cached results
        $resources2 = $service->discoverResources();

        $this->assertEquals($resources1, $resources2);
        $this->assertSame($resources1, $resources2);
    }
}
