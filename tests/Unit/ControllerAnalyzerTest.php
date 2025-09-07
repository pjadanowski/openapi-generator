<?php

namespace Pjadanowski\OpenApiGenerator\Tests\Unit;

use Pjadanowski\OpenApiGenerator\Tests\TestCase;
use Pjadanowski\OpenApiGenerator\Services\ControllerAnalyzer;
use Pjadanowski\OpenApiGenerator\Tests\Fixtures\TestController;
use ReflectionMethod;
use PHPUnit\Framework\Attributes\Test;

class ControllerAnalyzerTest extends TestCase
{
    #[Test]
    public function it_can_get_method_description()
    {
        $service = app(ControllerAnalyzer::class);
        $reflection = new ReflectionMethod(TestController::class, 'singleUser');

        $description = $service->getMethodDescription($reflection);

        $this->assertIsString($description);
        $this->assertNotEmpty($description);
    }

    #[Test]
    public function it_can_get_method_tags()
    {
        $service = app(ControllerAnalyzer::class);
        $reflection = new ReflectionMethod(TestController::class, 'singleUser');

        $tags = $service->getMethodTags($reflection);

        $this->assertIsArray($tags);
    }

    #[Test]
    public function it_can_detect_deprecated_methods()
    {
        $service = app(ControllerAnalyzer::class);
        $reflection = new ReflectionMethod(TestController::class, 'singleUser');

        $isDeprecated = $service->isDeprecated($reflection);

        $this->assertIsBool($isDeprecated);
        $this->assertFalse($isDeprecated); // singleUser should not be deprecated
    }

    #[Test]
    public function it_can_get_response_codes()
    {
        $service = app(ControllerAnalyzer::class);
        $reflection = new ReflectionMethod(TestController::class, 'singleUser');

        $responseCodes = $service->getResponseCodes($reflection);

        $this->assertIsArray($responseCodes);
    }

    #[Test]
    public function it_generates_default_description_when_no_docblock()
    {
        $service = app(ControllerAnalyzer::class);
        $reflection = new ReflectionMethod(TestController::class, 'singleUser');

        $description = $service->getMethodDescription($reflection);

        $this->assertIsString($description);
        $this->assertTrue(str_contains($description, 'singleUser'));
        $this->assertTrue(str_contains($description, 'TestController'));
    }
}
