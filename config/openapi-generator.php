<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Information
    |--------------------------------------------------------------------------
    |
    | Default information for the generated OpenAPI documentation.
    |
    */
    'info' => [
        'title' => env('APP_NAME', 'Laravel') . ' API Documentation',
        'version' => '1.0.0',
        'description' => 'API documentation generated from Laravel routes and controllers',
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the generated documentation output.
    |
    */
    'output' => [
        'path' => 'openapi.json',
        'format' => 'json', // json or yaml
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Filtering
    |--------------------------------------------------------------------------
    |
    | Configuration for which routes to include in documentation.
    |
    */
    'routes' => [
        'include_patterns' => [
            'api/*',        // Include all API routes
        ],
        'exclude_patterns' => [
            'api/internal/*',   // Exclude internal API routes
            'api/debug/*',      // Exclude debug routes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Discovery
    |--------------------------------------------------------------------------
    |
    | Paths where Resource classes should be discovered.
    |
    */
    'resource_paths' => [
        // Default paths - can be overridden in env setup
    ],

    /*
    |--------------------------------------------------------------------------
    | Swagger UI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the built-in Swagger UI viewer.
    |
    */
    'swagger' => [
        'enabled' => true,
        'route' => '/api/documentation',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | ReDoc Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the built-in ReDoc viewer.
    |
    */
    'redoc' => [
        'enabled' => false,
        'route' => '/api/redoc',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Controller Analysis
    |--------------------------------------------------------------------------
    |
    | Configuration for controller method analysis.
    |
    */
    'controllers' => [
        'parse_docblocks' => true,
        'default_responses' => [
            '400' => 'Bad Request',
            '401' => 'Unauthorized',
            '403' => 'Forbidden',
            '404' => 'Not Found',
            '422' => 'Validation Error',
            '500' => 'Internal Server Error',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Class Analysis
    |--------------------------------------------------------------------------
    |
    | Configuration for Spatie Data class analysis.
    |
    */
    'data_classes' => [
        'recursive_analysis' => true,
        'include_timestamps' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | FormRequest Analysis  
    |--------------------------------------------------------------------------
    |
    | Configuration for Laravel FormRequest analysis.
    |
    */
    'form_requests' => [
        'parse_validation_rules' => true,
        'include_custom_rules' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Analysis
    |--------------------------------------------------------------------------
    |
    | Configuration for Laravel Resource analysis.
    |
    */
    'resources' => [
        'parse_to_array' => true,
        'detect_collections' => true,
    ],
];
