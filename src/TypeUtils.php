<?php


namespace W2w\Lib\ApieObjectAccessNormalizer;

use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\PropertyInfo\Type;

class TypeUtils
{
    /**
     * @param (ReflectionMethod|ReflectionProperty)[] $methods
     * @return Type[]
     */
    public static function convertToTypeArray(array $methods)
    {
        $res = [];
        foreach ($methods as $method) {
            if ($method instanceof ReflectionMethod) {
                $type = self::convertMethodToType($method);
                if ($type) {
                    $res[] = $type;
                }
            } elseif ($method instanceof ReflectionProperty) {
                $type = self::convertPropertyToType($method);
                if ($type) {
                    $res[] = $type;
                }
            }
        }
        return $res;
    }

    public static function convertMethodToType(ReflectionMethod $method): ?Type
    {
        $parameters = $method->getParameters();
        $parameter = reset($parameters);
        $type = null;
        if ($parameter && !$parameter->isOptional()) {
            $type = $parameter->getType();
        } elseif (!$parameter) {
            $type = $method->getReturnType();
        }
        if (!$type) {
            return null;
        }
        if ($type->isBuiltin()) {
            return new Type($type->getName(), $type->allowsNull());
        }
        return new Type(Type::BUILTIN_TYPE_OBJECT, $type->allowsNull(), $type->getName());
    }

    /**
     * @param ReflectionProperty $property
     * @return Type|null
     */
    public static function convertPropertyToType(ReflectionProperty $property): ?Type
    {
        if (PHP_VERSION_ID >= 70400) {
            $type = $property->getType();
            if (!$type) {
                return null;
            }
            if ($type->isBuiltin()) {
                return new Type($type->getName(), $type->allowsNull());
            }
            return new Type(Type::BUILTIN_TYPE_OBJECT, $type->allowsNull(), $type->getName());
        }
        return null;
    }
}
