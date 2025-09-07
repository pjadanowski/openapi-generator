<?php

namespace Pjadanowski\OpenApiGenerator\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class SwaggerController extends Controller
{
    public function index()
    {
        $config = config('openapi-generator');
        $openApiUrl = url($config['output']['path']);

        return view('openapi-generator::swagger', compact('openApiUrl'));
    }

    public function redoc()
    {
        $config = config('openapi-generator');
        $openApiUrl = url($config['output']['path']);

        return view('openapi-generator::redoc', compact('openApiUrl'));
    }

    public function spec()
    {
        $config = config('openapi-generator');
        $specPath = public_path($config['output']['path']);

        if (!file_exists($specPath)) {
            abort(404, 'OpenAPI specification not found. Run php artisan openapi:generate first.');
        }

        $content = file_get_contents($specPath);
        $extension = pathinfo($specPath, PATHINFO_EXTENSION);

        $contentType = $extension === 'yaml' ? 'application/x-yaml' : 'application/json';

        return response($content, 200, [
            'Content-Type' => $contentType,
        ]);
    }
}
