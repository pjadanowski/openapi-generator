<?php

namespace Pjadanowski\OpenApiGenerator\Services;

use cebe\openapi\spec\Schema;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use ReflectionClass;
use ReflectionMethod;

class ResourceAnalyzer
{
    private DataClassAnalyzer $dataClassAnalyzer;
    private ResourceDiscoveryService $resourceDiscovery;
    private array $schemas = [];
    private array $processedResources = [];

    public function __construct(DataClassAnalyzer $dataClassAnalyzer, ResourceDiscoveryService $resourceDiscovery)
    {
        $this->dataClassAnalyzer = $dataClassAnalyzer;
        $this->resourceDiscovery = $resourceDiscovery;
    }

    public function analyzeResource(string $resourceClass): Schema
    {
        if (!is_subclass_of($resourceClass, JsonResource::class)) {
            throw new \InvalidArgumentException("Class {$resourceClass} must extend JsonResource");
        }

        // Check if already processed
        $schemaName = $this->getSchemaName($resourceClass);
        if (in_array($resourceClass, $this->processedResources)) {
            return new Schema(['$ref' => "#/components/schemas/{$schemaName}"]);
        }

        $this->processedResources[] = $resourceClass;

        $reflection = new ReflectionClass($resourceClass);

        // Check if it's a ResourceCollection
        if (is_subclass_of($resourceClass, ResourceCollection::class)) {
            return $this->analyzeResourceCollection($resourceClass);
        }

        // Analyze toArray method
        if ($reflection->hasMethod('toArray')) {
            $toArrayMethod = $reflection->getMethod('toArray');
            $schema = $this->analyzeToArrayMethod($toArrayMethod, $resourceClass);

            // Store in schemas for components
            $this->schemas[$schemaName] = $schema;

            return new Schema(['$ref' => "#/components/schemas/{$schemaName}"]);
        }

        $defaultSchema = new Schema(['type' => 'object']);
        $this->schemas[$schemaName] = $defaultSchema;
        return new Schema(['$ref' => "#/components/schemas/{$schemaName}"]);
    }

    public function analyzeJsonResponse(ReflectionMethod $method): \cebe\openapi\spec\Response
    {
        // Analyze method body for response patterns
        $docComment = $method->getDocComment();

        if ($docComment) {
            // Look for @return annotations
            if (preg_match('/@return\s+.*Resource/', $docComment, $matches)) {
                // Extract resource class name
                preg_match('/([A-Z]\w*Resource)/', $matches[0], $resourceMatch);
                if (!empty($resourceMatch[1])) {
                    $resourceClass = $this->resolveResourceClass($resourceMatch[1], $method);
                    if ($resourceClass) {
                        $schema = $this->analyzeResource($resourceClass);
                        return new \cebe\openapi\spec\Response([
                            'description' => 'Successful response',
                            'content' => [
                                'application/json' => new \cebe\openapi\spec\MediaType([
                                    'schema' => $schema
                                ])
                            ]
                        ]);
                    }
                }
            }
        }

        return new \cebe\openapi\spec\Response([
            'description' => 'Successful response',
            'content' => [
                'application/json' => new \cebe\openapi\spec\MediaType([
                    'schema' => new Schema(['type' => 'object'])
                ])
            ]
        ]);
    }

    private function analyzeResourceCollection(string $resourceClass): Schema
    {
        // For collections, try to determine the item type
        $reflection = new ReflectionClass($resourceClass);

        if ($reflection->hasProperty('collects')) {
            $collectsProperty = $reflection->getProperty('collects');
            $collectsProperty->setAccessible(true);

            try {
                $instance = $reflection->newInstanceWithoutConstructor();
                $itemClass = $collectsProperty->getValue($instance);

                if ($itemClass && is_subclass_of($itemClass, JsonResource::class)) {
                    $itemSchema = $this->analyzeResource($itemClass);
                    return new Schema([
                        'type' => 'array',
                        'items' => $itemSchema
                    ]);
                }
            } catch (\Exception $e) {
                // Continue with fallback
            }
        }

        return new Schema([
            'type' => 'array',
            'items' => new Schema(['type' => 'object'])
        ]);
    }

    private function analyzeToArrayMethod(ReflectionMethod $method, string $resourceClass): Schema
    {
        $properties = [];

        // Try to get the source file and analyze the toArray method
        $filename = $method->getFileName();
        if ($filename) {
            $source = file_get_contents($filename);
            $lines = explode("\n", $source);

            $startLine = $method->getStartLine() - 1;
            $endLine = $method->getEndLine() - 1;

            $methodSource = implode("\n", array_slice($lines, $startLine, $endLine - $startLine + 1));

            // Enhanced regex to find array key patterns (handles multiline)
            if (preg_match('/return\s*\[(.*?)\];/s', $methodSource, $matches)) {
                $arrayContent = $matches[1];

                // Extract key patterns more robustly
                preg_match_all("/['\"](\w+)['\"]?\s*=>/", $arrayContent, $keyMatches);

                foreach ($keyMatches[1] as $key) {
                    // Determine type based on common patterns
                    $properties[$key] = $this->guessPropertyType($key, $arrayContent);
                }
            }

            // Fallback: extract from $this->property patterns
            if (empty($properties)) {
                preg_match_all('/\$this->(\w+)/', $methodSource, $propertyMatches);
                foreach ($propertyMatches[1] as $property) {
                    $properties[$property] = new Schema(['type' => 'string']);
                }
            }
        }

        if (empty($properties)) {
            // Fallback: analyze based on common resource patterns
            $properties = $this->getDefaultResourceProperties($resourceClass);
        }

        return new Schema([
            'type' => 'object',
            'properties' => $properties
        ]);
    }

    private function guessPropertyType(string $key, string $context): Schema
    {
        // Look for type hints in the context around this key
        if (preg_match("/'$key'\s*=>\s*\\\$this->$key/", $context)) {
            // Check common field patterns
            if (in_array($key, ['id', 'user_id', 'age'])) {
                return new Schema(['type' => 'integer']);
            }
            if (in_array($key, ['email'])) {
                return new Schema(['type' => 'string', 'format' => 'email']);
            }
            if (in_array($key, ['created_at', 'updated_at'])) {
                return new Schema(['type' => 'string', 'format' => 'date-time']);
            }
            if (str_contains($key, 'address')) {
                return new Schema(['type' => 'object']);
            }
        }

        return new Schema(['type' => 'string']);
    }

    private function getDefaultResourceProperties(string $resourceClass): array
    {
        // Default properties based on resource name
        $className = class_basename($resourceClass);
        $modelName = str_replace('Resource', '', $className);

        return [
            'id' => new Schema(['type' => 'integer']),
            'created_at' => new Schema(['type' => 'string', 'format' => 'date-time']),
            'updated_at' => new Schema(['type' => 'string', 'format' => 'date-time']),
        ];
    }

    /**
     * Analyze all discovered Resource classes automatically
     */
    public function analyzeAllResources(): array
    {
        $resourceClasses = $this->resourceDiscovery->getResourceClassNames();

        foreach ($resourceClasses as $resourceClass) {
            try {
                $this->analyzeResource($resourceClass);
            } catch (\Exception $e) {
                // Skip resources that can't be analyzed
                continue;
            }
        }

        return $this->schemas;
    }

    /**
     * Get Resource schema by class name or short name
     */
    public function getResourceSchema(string $resourceIdentifier): ?Schema
    {
        // Try by full class name first
        $schemaName = $this->getSchemaName($resourceIdentifier);
        if (isset($this->schemas[$schemaName])) {
            return new Schema(['$ref' => "#/components/schemas/{$schemaName}"]);
        }

        // Try to find and analyze the resource
        $resourceClass = $this->resourceDiscovery->findResourceByName($resourceIdentifier);
        if ($resourceClass) {
            return $this->analyzeResource($resourceClass);
        }

        return null;
    }

    private function resolveResourceClass(string $resourceName, ReflectionMethod $method): ?string
    {
        // First try using ResourceDiscoveryService
        $resourceClass = $this->resourceDiscovery->findResourceByName($resourceName);
        if ($resourceClass) {
            return $resourceClass;
        }

        // Fallback to original logic
        $declaringClass = $method->getDeclaringClass();
        $namespace = $declaringClass->getNamespaceName();

        // Try different possible namespaces
        $possibleClasses = [
            $namespace . '\\' . $resourceName,
            $namespace . '\\Resources\\' . $resourceName,
            'App\\Http\\Resources\\' . $resourceName,
        ];

        foreach ($possibleClasses as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    private function getSchemaName(string $resourceClass): string
    {
        $parts = explode('\\', $resourceClass);
        return end($parts);
    }

    public function getSchemas(): array
    {
        return $this->schemas;
    }

    public function resetSchemas(): void
    {
        $this->schemas = [];
        $this->processedResources = [];
    }
}
