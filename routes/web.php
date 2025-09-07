<?php

use Illuminate\Support\Facades\Route;
use Pjadanowski\OpenApiGenerator\Http\Controllers\SwaggerController;

Route::group(['prefix' => 'api'], function () {
    // Swagger UI route
    Route::get('docs', [SwaggerController::class, 'index'])
        ->name('openapi.swagger')
        ->middleware(config('openapi-generator.swagger.middleware', ['web']));

    // ReDoc route
    Route::get('redoc', [SwaggerController::class, 'redoc'])
        ->name('openapi.redoc')
        ->middleware(config('openapi-generator.redoc.middleware', ['web']));

    // OpenAPI spec route
    Route::get('openapi.json', [SwaggerController::class, 'spec'])
        ->name('openapi.spec');
});
