<?php

namespace Pjadanowski\OpenApiGenerator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Info;
use cebe\openapi\spec\Server;
use cebe\openapi\spec\Components;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Response;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use cebe\openapi\spec\MediaType;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Pjadanowski\OpenApiGenerator\Services\ControllerAnalyzer;
use Pjadanowski\OpenApiGenerator\Services\DataClassAnalyzer;
use Pjadanowski\OpenApiGenerator\Services\FormRequestAnalyzer;
use Pjadanowski\OpenApiGenerator\Services\ResourceAnalyzer;
use Pjadanowski\OpenApiGenerator\Services\ResourceDiscoveryService;

class RouteBasedOpenApiGenerator
{
    private array $schemas = [];
    private array $processedClasses = [];

    public function __construct(
        private ControllerAnalyzer $controllerAnalyzer,
        private DataClassAnalyzer $dataClassAnalyzer,
        private FormRequestAnalyzer $formRequestAnalyzer,
        private ResourceAnalyzer $resourceAnalyzer,
        private ResourceDiscoveryService $resourceDiscovery,
        private Router $router
    ) {}

    public function generateFromRoutes(array $options = []): OpenApi
    {
        $this->schemas = [];
        $this->processedClasses = [];

        // Reset analyzers
        $this->dataClassAnalyzer->resetSchemas();
        $this->resourceAnalyzer->resetSchemas();

        // Pre-discover and analyze all Resource classes
        $this->resourceAnalyzer->analyzeAllResources();

        $openApi = new OpenApi([
            'openapi' => '3.0.3',
            'info' => new Info([
                'title' => $options['title'] ?? 'API Documentation',
                'version' => $options['version'] ?? '1.0.0',
                'description' => $options['description'] ?? 'Generated from Laravel routes and controllers',
            ]),
            'servers' => [
                new Server([
                    'url' => config('app.url', 'http://localhost:8000'),
                    'description' => 'Development server'
                ])
            ],
            'paths' => [],
            'components' => new Components([
                'schemas' => []
            ])
        ]);

        $routes = $this->getApiRoutes();
        $paths = [];

        foreach ($routes as $route) {
            $pathItem = $this->processRoute($route);
            if ($pathItem) {
                $uri = $this->normalizeUri($route->uri());
                if (!isset($paths[$uri])) {
                    $paths[$uri] = new PathItem([]);
                }

                foreach ($route->methods() as $method) {
                    if ($method !== 'HEAD') {
                        $methodName = strtolower($method);
                        $paths[$uri]->{$methodName} = $pathItem;
                    }
                }
            }
        }

        $openApi->paths = $paths;

        // Collect schemas from all analyzers
        $allSchemas = array_merge(
            $this->schemas,
            $this->dataClassAnalyzer->getSchemas(),
            $this->resourceAnalyzer->getSchemas()
        );

        $openApi->components->schemas = $allSchemas;

        return $openApi;
    }

    private function getApiRoutes(): array
    {
        $allRoutes = $this->router->getRoutes();
        $apiRoutes = [];

        foreach ($allRoutes as $route) {
            // Filter API routes (can be customized)
            if (
                Str::startsWith($route->uri(), 'api/') ||
                in_array('api', $route->middleware()) ||
                $this->isApiRoute($route)
            ) {
                $apiRoutes[] = $route;
            }
        }

        return $apiRoutes;
    }

    private function isApiRoute(Route $route): bool
    {
        // Additional logic to determine if route is API route
        $action = $route->getAction();

        if (isset($action['controller'])) {
            $controller = $action['controller'];
            if (is_string($controller)) {
                return Str::contains($controller, 'Api\\') ||
                    Str::endsWith($controller, 'ApiController');
            }
        }

        return false;
    }

    private function processRoute(Route $route): ?Operation
    {
        $action = $route->getAction();

        if (!isset($action['controller'])) {
            return null;
        }

        [$controller, $method] = Str::parseCallback($action['controller']);

        if (!$controller || !$method) {
            return null;
        }

        try {
            $controllerReflection = new ReflectionClass($controller);
            $methodReflection = $controllerReflection->getMethod($method);

            $operation = new Operation([
                'summary' => $this->controllerAnalyzer->getMethodDescription($methodReflection),
                'description' => $this->generateSummary($route, $method),
                'parameters' => $this->getParameters($route, $methodReflection),
                'responses' => $this->getResponses($methodReflection),
            ]);

            // Add request body for POST/PUT/PATCH
            if (
                in_array('POST', $route->methods()) ||
                in_array('PUT', $route->methods()) ||
                in_array('PATCH', $route->methods())
            ) {
                $requestBody = $this->getRequestBody($methodReflection);
                if ($requestBody) {
                    $operation->requestBody = $requestBody;
                }
            }

            return $operation;
        } catch (\Exception $e) {
            // Log error and continue
            return null;
        }
    }

    private function generateSummary(Route $route, string $method): string
    {
        $uri = $route->uri();
        $httpMethod = $route->methods()[0] ?? 'GET';

        // Generate meaningful summary based on route
        $parts = explode('/', trim($uri, '/'));
        $resource = $parts[1] ?? 'resource';

        return match ($httpMethod) {
            'GET' => Str::contains($uri, '{') ? "Get {$resource}" : "List {$resource}",
            'POST' => "Create {$resource}",
            'PUT', 'PATCH' => "Update {$resource}",
            'DELETE' => "Delete {$resource}",
            default => ucfirst(strtolower($httpMethod)) . " {$resource}"
        };
    }

    private function getParameters(Route $route, ReflectionMethod $method): array
    {
        $parameters = [];

        // Path parameters
        preg_match_all('/\{(\w+)\??/', $route->uri(), $matches);
        foreach ($matches[1] as $param) {
            $parameters[] = new Parameter([
                'name' => $param,
                'in' => 'path',
                'required' => !Str::endsWith($matches[0][array_search($param, $matches[1])], '?}'),
                'schema' => new Schema(['type' => 'string'])
            ]);
        }

        // Query parameters from method signature
        foreach ($method->getParameters() as $param) {
            if ($this->isQueryParameter($param)) {
                $parameters[] = $this->createParameterFromReflection($param);
            }
        }

        return $parameters;
    }

    private function isQueryParameter(ReflectionParameter $param): bool
    {
        $type = $param->getType();
        if (!$type) {
            return false;
        }

        $typeName = $type->getName();

        // Skip Request objects, Data objects, and basic Laravel types
        return !is_subclass_of($typeName, \Illuminate\Http\Request::class) &&
            !is_subclass_of($typeName, \Spatie\LaravelData\Data::class) &&
            !in_array($typeName, ['int', 'string', 'bool', 'float', 'array']);
    }

    private function createParameterFromReflection(ReflectionParameter $param): Parameter
    {
        $type = $param->getType();
        $schema = $this->dataClassAnalyzer->convertReflectionTypeToSchema($type);

        return new Parameter([
            'name' => $param->getName(),
            'in' => 'query',
            'required' => !$param->isOptional(),
            'schema' => $schema
        ]);
    }

    private function getRequestBody(ReflectionMethod $method): ?RequestBody
    {
        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            if (!$type) {
                continue;
            }

            $typeName = $type->getName();

            // Check for FormRequest
            if (is_subclass_of($typeName, \Illuminate\Foundation\Http\FormRequest::class)) {
                $schema = $this->formRequestAnalyzer->analyzeFormRequest($typeName);
                $mediaType = new MediaType(['schema' => $schema]);
                return new RequestBody([
                    'required' => true,
                    'content' => [
                        'application/json' => $mediaType
                    ]
                ]);
            }

            // Check for Spatie Data
            if (is_subclass_of($typeName, \Spatie\LaravelData\Data::class)) {
                $schemaName = $this->dataClassAnalyzer->processDataClass($typeName);
                return new RequestBody([
                    'required' => true,
                    'content' => [
                        'application/json' => new MediaType([
                            'schema' => new Schema(['$ref' => "#/components/schemas/{$schemaName}"])
                        ])
                    ]
                ]);
            }
        }

        return null;
    }

    private function getResponses(ReflectionMethod $method): array
    {
        $responses = [];

        // First, check for responses from PHPDoc
        $docResponses = $this->controllerAnalyzer->getResponseCodes($method);
        foreach ($docResponses as $code => $description) {
            $responses[$code] = new Response(['description' => $description]);
        }

        // Analyze return type for main success response
        $returnType = $method->getReturnType();
        if ($returnType && !isset($responses['200']) && !isset($responses['201'])) {
            $response = $this->analyzeReturnType($returnType, $method);
            if ($response) {
                // Determine status code based on method name and HTTP method
                $statusCode = $this->getDefaultSuccessStatusCode($method);
                $responses[$statusCode] = $response;
            }
        }

        // Add default success response if none found
        if (!$this->hasSuccessResponse($responses)) {
            $statusCode = $this->getDefaultSuccessStatusCode($method);
            $responses[$statusCode] = new Response([
                'description' => $this->getDefaultSuccessDescription($method)
            ]);
        }

        // Add standard Laravel error responses based on method context
        $this->addStandardErrorResponses($responses, $method);

        return $responses;
    }

    private function hasSuccessResponse(array $responses): bool
    {
        foreach (array_keys($responses) as $code) {
            if ($code >= 200 && $code < 300) {
                return true;
            }
        }
        return false;
    }

    private function getDefaultSuccessStatusCode(ReflectionMethod $method): string
    {
        $methodName = strtolower($method->getName());

        // Common Laravel controller method patterns
        return match (true) {
            str_contains($methodName, 'store') || str_contains($methodName, 'create') => '201',
            str_contains($methodName, 'destroy') || str_contains($methodName, 'delete') => '204',
            default => '200'
        };
    }

    private function getDefaultSuccessDescription(ReflectionMethod $method): string
    {
        $methodName = strtolower($method->getName());

        return match (true) {
            str_contains($methodName, 'index') || str_contains($methodName, 'list') => 'List retrieved successfully',
            str_contains($methodName, 'show') || str_contains($methodName, 'get') => 'Resource retrieved successfully',
            str_contains($methodName, 'store') || str_contains($methodName, 'create') => 'Resource created successfully',
            str_contains($methodName, 'update') || str_contains($methodName, 'edit') => 'Resource updated successfully',
            str_contains($methodName, 'destroy') || str_contains($methodName, 'delete') => 'Resource deleted successfully',
            default => 'Successful response'
        };
    }

    private function addStandardErrorResponses(array &$responses, ReflectionMethod $method): void
    {
        $config = config('openapi-generator.controllers.default_responses', []);

        // Always add these standard responses if not already present
        $standardResponses = [
            '400' => 'Bad Request',
            '401' => 'Unauthorized',
            '500' => 'Internal Server Error',
        ];

        // Add context-specific responses
        $methodName = strtolower($method->getName());
        $parameters = $method->getParameters();

        // Add 404 for methods that fetch specific resources
        if (
            str_contains($methodName, 'show') ||
            str_contains($methodName, 'update') ||
            str_contains($methodName, 'destroy') ||
            $this->hasIdParameter($parameters) ||
            $this->methodContainsFindOrFail($method)
        ) {
            $standardResponses['404'] = 'Resource not found';
        }

        // Add 422 for methods that validate input
        if ($this->hasValidationInput($parameters)) {
            $standardResponses['422'] = 'Validation error';
        }

        // Add 403 for methods that might require authorization
        if (
            str_contains($methodName, 'store') ||
            str_contains($methodName, 'update') ||
            str_contains($methodName, 'destroy')
        ) {
            $standardResponses['403'] = 'Forbidden';
        }

        // Merge with config defaults (use + to preserve string keys)
        $allResponses = $standardResponses + $config;

        // Add responses that don't already exist
        foreach ($allResponses as $code => $description) {
            if (!isset($responses[$code])) {
                $responses[$code] = new Response([
                    'description' => $description,
                    'content' => $code === '422' ? $this->getValidationErrorContent() : null
                ]);
            }
        }
    }

    private function hasIdParameter(array $parameters): bool
    {
        foreach ($parameters as $param) {
            $name = strtolower($param->getName());
            if (
                in_array($name, ['id', 'user_id', 'post_id', 'product_id']) ||
                str_ends_with($name, '_id')
            ) {
                return true;
            }
        }
        return false;
    }

    private function hasValidationInput(array $parameters): bool
    {
        foreach ($parameters as $param) {
            $type = $param->getType();
            if (!$type) continue;

            $typeName = $type->getName();

            // Check for FormRequest or Spatie Data
            if (
                is_subclass_of($typeName, \Illuminate\Foundation\Http\FormRequest::class) ||
                is_subclass_of($typeName, \Spatie\LaravelData\Data::class)
            ) {
                return true;
            }
        }

        return false;
    }

    private function methodContainsFindOrFail(ReflectionMethod $method): bool
    {
        try {
            // Get the file containing the method
            $fileName = $method->getFileName();
            if (!$fileName || !file_exists($fileName)) {
                return false;
            }

            // Read the source file
            $source = file_get_contents($fileName);
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();

            if (!$startLine || !$endLine) {
                return false;
            }

            // Extract the method source code
            $lines = explode("\n", $source);
            $methodSource = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

            // Check for findOrFail patterns
            return preg_match('/\b\w+::(findOrFail|find.*OrFail)\s*\(/', $methodSource) ||
                preg_match('/\$\w+->findOrFail\s*\(/', $methodSource) ||
                preg_match('/\$\w+->find.*OrFail\s*\(/', $methodSource);
        } catch (\Exception $e) {
            // If we can't analyze the source, fall back to safe defaults
            return false;
        }
    }

    private function getValidationErrorContent(): array
    {
        return [
            'application/json' => new MediaType([
                'schema' => new Schema([
                    'type' => 'object',
                    'properties' => [
                        'message' => new Schema([
                            'type' => 'string',
                            'example' => 'The given data was invalid.'
                        ]),
                        'errors' => new Schema([
                            'type' => 'object',
                            'additionalProperties' => new Schema([
                                'type' => 'array',
                                'items' => new Schema(['type' => 'string'])
                            ]),
                            'example' => [
                                'email' => ['The email field is required.'],
                                'name' => ['The name must be at least 3 characters.']
                            ]
                        ])
                    ]
                ])
            ])
        ];
    }

    private function analyzeReturnType(\ReflectionType $returnType, ReflectionMethod $method): ?Response
    {
        if ($returnType instanceof \ReflectionNamedType) {
            $typeName = $returnType->getName();

            // Check for AnonymousResourceCollection FIRST (before JsonResource check)
            if ($typeName === \Illuminate\Http\Resources\Json\AnonymousResourceCollection::class) {
                // Try to determine the resource type from method body or PHPDoc
                $resourceClass = $this->extractResourceFromMethod($method);

                if ($resourceClass && is_subclass_of($resourceClass, \Illuminate\Http\Resources\Json\JsonResource::class)) {
                    $itemSchema = $this->resourceAnalyzer->analyzeResource($resourceClass);
                    $schema = new Schema([
                        'type' => 'object',
                        'properties' => [
                            'data' => new Schema([
                                'type' => 'array',
                                'items' => $itemSchema
                            ]),
                            // Add pagination properties if needed
                        ]
                    ]);
                    return new Response([
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => new MediaType([
                                'schema' => $schema
                            ])
                        ]
                    ]);
                }
            }

            // Check for JSON Response
            if ($typeName === \Illuminate\Http\JsonResponse::class) {
                return $this->resourceAnalyzer->analyzeJsonResponse($method);
            }

            // Check for Resource (but exclude AnonymousResourceCollection)
            if (
                $typeName !== \Illuminate\Http\Resources\Json\AnonymousResourceCollection::class &&
                is_subclass_of($typeName, \Illuminate\Http\Resources\Json\JsonResource::class)
            ) {
                $schema = $this->resourceAnalyzer->analyzeResource($typeName);
                return new Response([
                    'description' => 'Successful response',
                    'content' => [
                        'application/json' => new MediaType([
                            'schema' => $schema
                        ])
                    ]
                ]);
            }
        }

        return new Response([
            'description' => 'Successful response',
            'content' => [
                'application/json' => new MediaType([
                    'schema' => new Schema(['type' => 'object'])
                ])
            ]
        ]);
    }

    private function normalizeUri(string $uri): string
    {
        // Convert Laravel route parameters to OpenAPI format
        $normalized = preg_replace('/\{(\w+)\?\}/', '{\1}', $uri);

        // Ensure URI starts with /
        if (!str_starts_with($normalized, '/')) {
            $normalized = '/' . $normalized;
        }

        return $normalized;
    }

    private function extractResourceFromMethod(ReflectionMethod $method): ?string
    {
        // 1. First try to parse PHPDoc for clear type hints
        $docComment = $method->getDocComment();
        if ($docComment) {
            // Look for @return UserResource::collection() or similar
            if (preg_match('/@return\s+.*?(\w+Resource)(?:::collection\(\))?/', $docComment, $matches)) {
                $resourceName = $matches[1];
                $resourceClass = $this->resourceDiscovery->findResourceByName($resourceName);
                if ($resourceClass) {
                    return $resourceClass;
                }
            }
        }

        // 2. Parse method source code for patterns like UserResource::collection()
        try {
            $filename = $method->getDeclaringClass()->getFileName();
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();

            if ($filename && $startLine && $endLine) {
                $lines = array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1);
                $methodSource = implode('', $lines);

                // Look for pattern like "UserResource::collection"
                if (preg_match('/(\w+Resource)::collection/', $methodSource, $matches)) {
                    $resourceName = $matches[1];
                    $resourceClass = $this->resourceDiscovery->findResourceByName($resourceName);
                    if ($resourceClass) {
                        return $resourceClass;
                    }
                }

                // Look for direct Resource class instantiation
                if (preg_match('/new\s+(\w+Resource)\s*\(/', $methodSource, $matches)) {
                    $resourceName = $matches[1];
                    $resourceClass = $this->resourceDiscovery->findResourceByName($resourceName);
                    if ($resourceClass) {
                        return $resourceClass;
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore errors in source parsing
        }

        // 3. Fallback: guess based on controller name pattern
        $controllerName = $method->getDeclaringClass()->getShortName();
        if (str_contains($controllerName, 'Controller')) {
            $resourceName = str_replace('Controller', 'Resource', $controllerName);
            $resourceClass = $this->resourceDiscovery->findResourceByName($resourceName);
            if ($resourceClass) {
                return $resourceClass;
            }
        }

        return null;
    }
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    public function addSchema(string $name, Schema $schema): void
    {
        $this->schemas[$name] = $schema;
    }
}
