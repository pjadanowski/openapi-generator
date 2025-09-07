<?php

namespace Pjadanowski\OpenApiGenerator\Services;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class ControllerAnalyzer
{
    public function getMethodDescription(ReflectionMethod $method): string
    {
        $docComment = $method->getDocComment();
        
        if ($docComment) {
            // Extract description from PHPDoc
            $lines = explode("\n", $docComment);
            $description = '';
            
            foreach ($lines as $line) {
                $line = trim($line, " \t\n\r\0\x0B*");
                if (empty($line) || $line === '/**' || $line === '*/') {
                    continue;
                }
                
                // Skip @param, @return, etc.
                if (str_starts_with($line, '@')) {
                    break;
                }
                
                $description .= $description ? ' ' . $line : $line;
            }
            
            return $description ?: $this->generateDefaultDescription($method);
        }
        
        return $this->generateDefaultDescription($method);
    }

    private function generateDefaultDescription(ReflectionMethod $method): string
    {
        $methodName = $method->getName();
        $className = $method->getDeclaringClass()->getShortName();
        
        return "Handle {$methodName} request in {$className}";
    }

    public function getMethodTags(ReflectionMethod $method): array
    {
        $docComment = $method->getDocComment();
        $tags = [];
        
        if ($docComment) {
            preg_match_all('/@(\w+)(?:\s+(.*))?/', $docComment, $matches, PREG_SET_ORDER);
            
            foreach ($matches as $match) {
                $tagName = $match[1];
                $tagValue = isset($match[2]) ? trim($match[2]) : true;
                
                if (!isset($tags[$tagName])) {
                    $tags[$tagName] = [];
                }
                
                $tags[$tagName][] = $tagValue;
            }
        }
        
        return $tags;
    }

    public function isDeprecated(ReflectionMethod $method): bool
    {
        $tags = $this->getMethodTags($method);
        return isset($tags['deprecated']);
    }

    public function getResponseCodes(ReflectionMethod $method): array
    {
        $tags = $this->getMethodTags($method);
        $codes = [];
        
        if (isset($tags['response'])) {
            foreach ($tags['response'] as $response) {
                if (preg_match('/^(\d{3})\s*(.*)/', $response, $matches)) {
                    $codes[$matches[1]] = $matches[2] ?: 'Response';
                }
            }
        }
        
        return $codes;
    }
}
