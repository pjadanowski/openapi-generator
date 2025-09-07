<?php

namespace SpatieData\OpenApiGenerator\Services;

use Symfony\Component\Finder\Finder;
use ReflectionClass;
use Spatie\LaravelData\Data;

class DataClassDiscovery
{
    public function discoverDataClasses(string $path): array
    {
        $basePath = base_path($path);

        if (!is_dir($basePath)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()->in($basePath)->name('*.php');

        $dataClasses = [];

        foreach ($finder as $file) {
            $className = $this->getClassNameFromFile($file->getPathname());

            if ($className && $this->isDataClass($className)) {
                $dataClasses[] = $className;
            }
        }

        return $dataClasses;
    }

    private function getClassNameFromFile(string $filepath): ?string
    {
        $content = file_get_contents($filepath);

        // Extract namespace
        if (!preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches)) {
            return null;
        }

        $namespace = $namespaceMatches[1];

        // Extract class name
        if (!preg_match('/class\s+(\w+)/', $content, $classMatches)) {
            return null;
        }

        $className = $classMatches[1];

        return $namespace . '\\' . $className;
    }

    private function isDataClass(string $className): bool
    {
        if (!class_exists($className)) {
            return false;
        }

        try {
            $reflection = new ReflectionClass($className);
            return $reflection->isSubclassOf(Data::class);
        } catch (\Exception $e) {
            return false;
        }
    }
}
