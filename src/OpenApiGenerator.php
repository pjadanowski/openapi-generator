<?php

namespace Pjadanowski\OpenApiGenerator;

use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Info;
use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Components;
use ReflectionClass;
use ReflectionProperty;
use ReflectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Nullable;

class OpenApiGenerator
{
    private array $schemas = [];
    private array $processedClasses = [];

    public function generateFromDataClasses(array $dataClasses, array $info = []): OpenApi
    {
        $this->schemas = [];
        $this->processedClasses = [];

        $openApi = new OpenApi([
            'openapi' => '3.0.3',
            'info' => new Info([
                'title' => $info['title'] ?? 'API Documentation',
                'version' => $info['version'] ?? '1.0.0',
                'description' => $info['description'] ?? 'Generated from Spatie Data classes',
            ]),
            'components' => new Components([
                'schemas' => []
            ])
        ]);

        foreach ($dataClasses as $dataClass) {
            $this->processDataClass($dataClass);
        }

        $openApi->components->schemas = $this->schemas;

        return $openApi;
    }

    private function processDataClass(string $className): string
    {
        if (in_array($className, $this->processedClasses)) {
            return $this->getSchemaName($className);
        }

        $this->processedClasses[] = $className;

        $reflection = new ReflectionClass($className);

        if (!$reflection->isSubclassOf(Data::class)) {
            throw new \InvalidArgumentException("Class {$className} must extend Spatie\LaravelData\Data");
        }

        $schemaName = $this->getSchemaName($className);
        $properties = [];
        $required = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $propertyName = $property->getName();
            $propertySchema = $this->getPropertySchema($property);

            $properties[$propertyName] = $propertySchema;

            if ($this->isPropertyRequired($property)) {
                $required[] = $propertyName;
            }
        }

        $schema = new Schema([
            'type' => 'object',
            'properties' => $properties,
        ]);

        if (!empty($required)) {
            $schema->required = $required;
        }

        $this->schemas[$schemaName] = $schema;

        return $schemaName;
    }

    private function getPropertySchema(ReflectionProperty $property): Schema
    {
        $type = $property->getType();

        if ($type === null) {
            return new Schema(['type' => 'string']);
        }

        return $this->convertReflectionTypeToSchema($type, $property);
    }

    private function convertReflectionTypeToSchema(ReflectionType $type, ReflectionProperty $property): Schema
    {
        if ($type instanceof ReflectionUnionType) {
            return $this->handleUnionType($type, $property);
        }

        if ($type instanceof ReflectionNamedType) {
            return $this->handleNamedType($type, $property);
        }

        return new Schema(['type' => 'string']);
    }

    private function handleNamedType(ReflectionNamedType $type, ReflectionProperty $property): Schema
    {
        $typeName = $type->getName();

        // Handle built-in types
        switch ($typeName) {
            case 'string':
                return new Schema(['type' => 'string']);
            case 'int':
            case 'integer':
                return new Schema(['type' => 'integer']);
            case 'float':
            case 'double':
                return new Schema(['type' => 'number', 'format' => 'float']);
            case 'bool':
            case 'boolean':
                return new Schema(['type' => 'boolean']);
            case 'array':
                return new Schema(['type' => 'array', 'items' => new Schema(['type' => 'string'])]);
            case 'object':
                return new Schema(['type' => 'object']);
            default:
                // Check if it's a Data class
                if (class_exists($typeName) && is_subclass_of($typeName, Data::class)) {
                    $schemaName = $this->processDataClass($typeName);
                    return new Schema(['$ref' => "#/components/schemas/{$schemaName}"]);
                }

                // Check if it's a collection
                if ($this->isCollectionType($typeName)) {
                    return $this->handleCollectionType($property);
                }

                return new Schema(['type' => 'string']);
        }
    }

    private function handleUnionType(ReflectionUnionType $type, ReflectionProperty $property): Schema
    {
        $types = $type->getTypes();
        $nonNullTypes = array_filter($types, fn($t) => $t->getName() !== 'null');

        if (count($nonNullTypes) === 1) {
            $schema = $this->handleNamedType(reset($nonNullTypes), $property);
            $schema->nullable = true;
            return $schema;
        }

        // For complex union types, use oneOf
        $schemas = [];
        foreach ($nonNullTypes as $unionType) {
            $schemas[] = $this->handleNamedType($unionType, $property);
        }

        return new Schema([
            'oneOf' => $schemas
        ]);
    }

    private function isCollectionType(string $typeName): bool
    {
        return in_array($typeName, [
            'Illuminate\Support\Collection',
            'Spatie\LaravelData\DataCollection',
        ]) || str_ends_with($typeName, 'Collection');
    }

    private function handleCollectionType(ReflectionProperty $property): Schema
    {
        // Try to get generic type from DocBlock
        $docComment = $property->getDocComment();
        if ($docComment && preg_match('/@var\s+\S*Collection<\s*(\S+)\s*>/', $docComment, $matches)) {
            $itemType = $matches[1];
            if (class_exists($itemType) && is_subclass_of($itemType, Data::class)) {
                $schemaName = $this->processDataClass($itemType);
                return new Schema([
                    'type' => 'array',
                    'items' => new Schema(['$ref' => "#/components/schemas/{$schemaName}"])
                ]);
            }
        }

        return new Schema(['type' => 'array', 'items' => new Schema(['type' => 'string'])]);
    }

    private function isPropertyRequired(ReflectionProperty $property): bool
    {
        $attributes = $property->getAttributes();

        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();

            if ($attributeName === Required::class) {
                return true;
            }

            if ($attributeName === Nullable::class) {
                return false;
            }
        }

        // Check if type is nullable
        $type = $property->getType();
        if ($type && $type->allowsNull()) {
            return false;
        }

        return true;
    }

    private function getSchemaName(string $className): string
    {
        $parts = explode('\\', $className);
        return end($parts);
    }

    public function toJson(): string
    {
        return json_encode($this->schemas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
