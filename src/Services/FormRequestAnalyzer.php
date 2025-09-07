<?php

namespace Pjadanowski\OpenApiGenerator\Services;

use cebe\openapi\spec\Schema;
use Illuminate\Foundation\Http\FormRequest;
use ReflectionClass;
use ReflectionMethod;

class FormRequestAnalyzer
{
    public function analyzeFormRequest(string $formRequestClass): Schema
    {
        if (!is_subclass_of($formRequestClass, FormRequest::class)) {
            throw new \InvalidArgumentException("Class {$formRequestClass} must extend FormRequest");
        }

        $reflection = new ReflectionClass($formRequestClass);
        $properties = [];
        $required = [];

        // Get validation rules
        $rules = $this->extractValidationRules($formRequestClass);

        foreach ($rules as $field => $rule) {
            $fieldSchema = $this->convertValidationRuleToSchema($rule);
            $properties[$field] = $fieldSchema;

            if ($this->isFieldRequired($rule)) {
                $required[] = $field;
            }
        }

        $schema = new Schema([
            'type' => 'object',
            'properties' => $properties,
        ]);

        if (!empty($required)) {
            $schema->required = $required;
        }

        return $schema;
    }

    private function extractValidationRules(string $formRequestClass): array
    {
        try {
            $formRequest = new $formRequestClass();
            
            // Try to get rules method
            $reflection = new ReflectionClass($formRequestClass);
            if ($reflection->hasMethod('rules')) {
                $rulesMethod = $reflection->getMethod('rules');
                $rulesMethod->setAccessible(true);
                return $rulesMethod->invoke($formRequest) ?: [];
            }
            
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function convertValidationRuleToSchema($rule): Schema
    {
        if (is_string($rule)) {
            $rules = explode('|', $rule);
        } elseif (is_array($rule)) {
            $rules = $rule;
        } else {
            return new Schema(['type' => 'string']);
        }

        $type = 'string';
        $format = null;
        $nullable = false;
        $enum = null;
        $minimum = null;
        $maximum = null;

        foreach ($rules as $singleRule) {
            if (is_object($singleRule)) {
                $singleRule = get_class($singleRule);
            }

            $ruleName = is_string($singleRule) ? explode(':', $singleRule)[0] : $singleRule;

            switch ($ruleName) {
                case 'integer':
                case 'int':
                    $type = 'integer';
                    break;
                case 'numeric':
                case 'decimal':
                    $type = 'number';
                    break;
                case 'boolean':
                case 'bool':
                    $type = 'boolean';
                    break;
                case 'array':
                    $type = 'array';
                    break;
                case 'email':
                    $type = 'string';
                    $format = 'email';
                    break;
                case 'url':
                    $type = 'string';
                    $format = 'uri';
                    break;
                case 'date':
                    $type = 'string';
                    $format = 'date';
                    break;
                case 'date_format':
                    $type = 'string';
                    $format = 'date-time';
                    break;
                case 'nullable':
                    $nullable = true;
                    break;
                case 'in':
                    if (is_string($singleRule) && strpos($singleRule, ':') !== false) {
                        $values = explode(',', explode(':', $singleRule, 2)[1]);
                        $enum = $values;
                    }
                    break;
                case 'min':
                    if (is_string($singleRule) && strpos($singleRule, ':') !== false) {
                        $minimum = (int) explode(':', $singleRule, 2)[1];
                    }
                    break;
                case 'max':
                    if (is_string($singleRule) && strpos($singleRule, ':') !== false) {
                        $maximum = (int) explode(':', $singleRule, 2)[1];
                    }
                    break;
            }
        }

        $schema = new Schema(['type' => $type]);

        if ($format) {
            $schema->format = $format;
        }

        if ($nullable) {
            $schema->nullable = true;
        }

        if ($enum) {
            $schema->enum = $enum;
        }

        if ($minimum !== null) {
            if ($type === 'integer' || $type === 'number') {
                $schema->minimum = $minimum;
            } else {
                $schema->minLength = $minimum;
            }
        }

        if ($maximum !== null) {
            if ($type === 'integer' || $type === 'number') {
                $schema->maximum = $maximum;
            } else {
                $schema->maxLength = $maximum;
            }
        }

        return $schema;
    }

    private function isFieldRequired($rule): bool
    {
        if (is_string($rule)) {
            return str_contains($rule, 'required');
        }

        if (is_array($rule)) {
            foreach ($rule as $singleRule) {
                if (is_string($singleRule) && str_contains($singleRule, 'required')) {
                    return true;
                }
                if (is_object($singleRule)) {
                    $className = get_class($singleRule);
                    if (str_contains($className, 'Required')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
