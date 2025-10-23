<?php

namespace App\Traits;

use ReflectionClass;
use ReflectionMethod;

trait Relationships
{
    /**
     * Pega todos relacionamentos de um model específico e retorna uma string concatenando e separando por ','
     * Ex: 'relationship1,relationship2,relationship3...'
     *
     * @return string
     */
    public static function Relationships()
    {
        $instance = static::getInstance();
        $methods = (new ReflectionClass($instance))->getMethods();
        $relationships = [];

        foreach ($methods as $method) {
            // Check if the method is an Eloquent relationship
            if (method_exists($instance, $method->name) && is_callable([$instance, $method->name])) {
                $reflectionMethod = new ReflectionMethod($instance, $method->name);
                $returnType = $reflectionMethod->getReturnType();

                if ($returnType instanceof \ReflectionNamedType && strpos($returnType->getName(), 'Illuminate\Database\Eloquent\Relations') !== false) {
                    $relationships[] = $method->name;
                }
            }
        }

        return implode(',', $relationships);
    }

    protected static function getInstance(): mixed
    {
        return app(static::class);
    }
}
