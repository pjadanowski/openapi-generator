<?php

namespace Pjadanowski\OpenApiGenerator\Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Pjadanowski\OpenApiGenerator\RouteBasedOpenApiGenerator;
use Pjadanowski\OpenApiGenerator\Controllers\TestController;
use Pjadanowski\OpenApiGenerator\Tests\TestCase;

class AutomaticResponseCodesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->registerTestRoutes();
    }

    protected function registerTestRoutes(): void
    {
        Route::prefix('api')->group(function () {
            // Store method - should get 201, 422, 403, 400, 401, 500
            Route::post('/users', [TestController::class, 'store']);

            // Show method with ID - should get 200, 404, 400, 401, 500
            Route::get('/users/{id}', [TestController::class, 'show']);

            // Update method with ID + FormRequest - should get 200, 404, 422, 403, 400, 401, 500
            Route::put('/users/{id}', [TestController::class, 'update']);

            // Destroy method - should get 204, 404, 403, 400, 401, 500
            Route::delete('/users/{id}', [TestController::class, 'destroy']);

            // Method with findOrFail - should get 200, 404, 400, 401, 500
            Route::get('/users/{userId}/posts', [TestController::class, 'userPosts']);

            // Basic method - should get 200, 400, 401, 500
            Route::get('/custom-action', [TestController::class, 'customAction']);

            // Index method - should get 200, 400, 401, 500
            Route::get('/users', [TestController::class, 'index']);
        });
    }
    #[Test]
    public function test_store_method_gets_correct_response_codes(): void
    {
        $generator = app(RouteBasedOpenApiGenerator::class);
        $spec = $generator->generateFromRoutes();

        $usersPath = '/api/users';
        $this->assertTrue(isset($spec->paths[$usersPath]), 'Could not find users path: ' . $usersPath);
        $operation = $spec->paths[$usersPath]->post;

        // Store should have success response
        $this->assertTrue(isset($operation->responses['201']), '201 response should exist');

        // Should have validation error response due to FormRequest parameter
        $this->assertTrue(isset($operation->responses['422']), '422 response should exist');

        // Should have authorization error response
        $this->assertTrue(isset($operation->responses['403']), '403 response should exist');

        // Should have standard error responses
        $this->assertTrue(isset($operation->responses['400']), '400 response should exist');
        $this->assertTrue(isset($operation->responses['401']), '401 response should exist');
        $this->assertTrue(isset($operation->responses['500']), '500 response should exist');
    }

    #[Test]
    public function test_show_method_with_id_gets_404(): void
    {
        $generator = app(RouteBasedOpenApiGenerator::class);
        $spec = $generator->generateFromRoutes();

        $operation = $spec->paths['/api/users/{id}']->get;

        // Show with ID parameter should get: 200, 404, 400, 401, 500
        $this->assertTrue(isset($operation->responses['200']));
        $this->assertTrue(isset($operation->responses['404']));
        $this->assertTrue(isset($operation->responses['400']));
        $this->assertTrue(isset($operation->responses['401']));
        $this->assertTrue(isset($operation->responses['500']));
    }

    #[Test]
    public function test_update_method_with_id_gets_full_crud_responses(): void
    {
        $generator = app(RouteBasedOpenApiGenerator::class);
        $spec = $generator->generateFromRoutes();

        $operation = $spec->paths['/api/users/{id}']->put;

        // Update with ID + FormRequest should get: 200, 404, 422, 403, 400, 401, 500
        $this->assertTrue(isset($operation->responses['200']));
        $this->assertTrue(isset($operation->responses['404']));
        $this->assertTrue(isset($operation->responses['422']));
        $this->assertTrue(isset($operation->responses['403']));
        $this->assertTrue(isset($operation->responses['400']));
        $this->assertTrue(isset($operation->responses['401']));
        $this->assertTrue(isset($operation->responses['500']));
    }

    #[Test]
    public function test_destroy_method_gets_204(): void
    {
        $generator = app(RouteBasedOpenApiGenerator::class);
        $spec = $generator->generateFromRoutes();

        $operation = $spec->paths['/api/users/{id}']->delete;

        // Destroy should get: 204, 404, 403, 400, 401, 500
        $this->assertTrue(isset($operation->responses['204']));
        $this->assertTrue(isset($operation->responses['404']));
        $this->assertTrue(isset($operation->responses['403']));
        $this->assertTrue(isset($operation->responses['400']));
        $this->assertTrue(isset($operation->responses['401']));
        $this->assertTrue(isset($operation->responses['500']));
    }

    #[Test]
    public function test_findorfail_detection_adds_404(): void
    {
        $generator = app(RouteBasedOpenApiGenerator::class);
        $spec = $generator->generateFromRoutes();

        $operation = $spec->paths['/api/users/{userId}/posts']->get;

        // Method with findOrFail should get 404
        $this->assertTrue(isset($operation->responses['200']));
        $this->assertTrue(isset($operation->responses['404']));
        $this->assertTrue(isset($operation->responses['400']));
        $this->assertTrue(isset($operation->responses['401']));
        $this->assertTrue(isset($operation->responses['500']));
    }

    #[Test]
    public function test_basic_method_gets_standard_responses(): void
    {
        $generator = app(RouteBasedOpenApiGenerator::class);
        $spec = $generator->generateFromRoutes();

        $operation = $spec->paths['/api/custom-action']->get;

        // Basic method should get: 200, 400, 401, 500
        $this->assertTrue(isset($operation->responses['200']));
        $this->assertTrue(isset($operation->responses['400']));
        $this->assertTrue(isset($operation->responses['401']));
        $this->assertTrue(isset($operation->responses['500']));

        // Should NOT get CRUD-specific responses
        $this->assertFalse(isset($operation->responses['201']));
        $this->assertFalse(isset($operation->responses['204']));
        $this->assertFalse(isset($operation->responses['404']));
        $this->assertFalse(isset($operation->responses['422']));
        $this->assertFalse(isset($operation->responses['403']));
    }

    #[Test]
    public function test_index_method_basic_responses(): void
    {
        $generator = app(RouteBasedOpenApiGenerator::class);
        $spec = $generator->generateFromRoutes();

        $operation = $spec->paths['/api/users']->get;

        // Index method should get: 200, 400, 401, 500
        $this->assertTrue(isset($operation->responses['200']));
        $this->assertTrue(isset($operation->responses['400']));
        $this->assertTrue(isset($operation->responses['401']));
        $this->assertTrue(isset($operation->responses['500']));

        // Should NOT get ID-specific or validation responses
        $this->assertFalse(isset($operation->responses['404']));
        $this->assertFalse(isset($operation->responses['422']));
    }
}
