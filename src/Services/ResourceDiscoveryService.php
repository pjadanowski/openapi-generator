<?php

namespace PJAdanowski\OpenApiGenerator\Services;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\StructureDiscoverer\Discover;

class ResourceDiscoveryService
{
    private array $discoveredResources = [];
    private bool $hasDiscovered = false;

    /**
     * Discover all Resource classes in the application
     */
    public function discoverResources(): array
    {
        if ($this->hasDiscovered) {
            return $this->discoveredResources;
        }

        // Get configured paths or default to app directory
        $paths = config('openapi-generator.resource_paths', [app_path()]);

        foreach ($paths as $path) {
            // Convert relative paths to absolute
            if (!str_starts_with($path, '/')) {
                $path = base_path($path);
            }

            if (is_dir($path)) {
                $resources = Discover::in($path)
                    ->classes()
                    ->extending(JsonResource::class)
                    ->get();

                foreach ($resources as $resourceClassName) {
                    $this->discoveredResources[$resourceClassName] = $resourceClassName;
                }
            }
        }

        $this->hasDiscovered = true;

        return $this->discoveredResources;
    }

    /**
     * Find Resource class by name (e.g., 'UserResource')
     */
    public function findResourceByName(string $name): ?string
    {
        $resources = $this->discoverResources();

        foreach ($resources as $className => $resource) {
            if (class_basename($className) === $name) {
                return $className;
            }
        }

        return null;
    }

    /**
     * Get all discovered Resource class names
     */
    public function getResourceClassNames(): array
    {
        $resources = $this->discoverResources();
        return array_keys($resources);
    }

    /**
     * Check if a class is a Resource
     */
    public function isResourceClass(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        return is_subclass_of($className, JsonResource::class);
    }

    /**
     * Extract Resource class name from type hint or return type
     */
    public function extractResourceFromTypeHint(?string $typeHint): ?string
    {
        if (!$typeHint) {
            return null;
        }

        // Handle AnonymousResourceCollection<UserResource>
        if (str_contains($typeHint, 'AnonymousResourceCollection')) {
            if (preg_match('/AnonymousResourceCollection<([^>]+)>/', $typeHint, $matches)) {
                $resourceClass = trim($matches[1], '\\');
                return $this->resolveResourceClass($resourceClass);
            }
        }

        // Handle direct Resource class
        if (str_contains($typeHint, 'Resource')) {
            $resourceClass = trim($typeHint, '\\');
            return $this->resolveResourceClass($resourceClass);
        }

        return null;
    }

    /**
     * Resolve Resource class name to full qualified name
     */
    private function resolveResourceClass(string $resourceClass): ?string
    {
        // If it's already a full class name
        if (class_exists($resourceClass)) {
            return $this->isResourceClass($resourceClass) ? $resourceClass : null;
        }

        // Try to find by short name
        $fullClassName = $this->findResourceByName($resourceClass);
        if ($fullClassName && $this->isResourceClass($fullClassName)) {
            return $fullClassName;
        }

        // Try common namespaces
        $commonNamespaces = [
            'App\\Http\\Resources\\',
            'App\\Resources\\',
        ];

        foreach ($commonNamespaces as $namespace) {
            $fullName = $namespace . $resourceClass;
            if (class_exists($fullName) && $this->isResourceClass($fullName)) {
                return $fullName;
            }
        }

        return null;
    }
}
