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

    /**
     * Check if the method is a getter or setter and returns a typehint if available.
     *
     * @param ReflectionMethod $property
     * @return Type|null
     */
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
     * If PHP version is higher than 7.4 return typehint of property.
     *
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

    /**
     * Checks if a method's name is get.* or is.*, and can be called without parameters.
     */
    public static function isGetMethod(ReflectionMethod $method): bool
    {
        $methodLength = strlen($method->name);

        return
            !$method->isStatic() &&
            (
                ((0 === strpos($method->name, 'get') && 3 < $methodLength) ||
                    (0 === strpos($method->name, 'is') && 2 < $methodLength) ||
                    (0 === strpos($method->name, 'has') && 3 < $methodLength)) &&
                0 === $method->getNumberOfRequiredParameters()
            );
    }

    /**
     * Checks if a method's name is set.*  with 0 or 1 parameters.
     */
    public static function isSetMethod(ReflectionMethod $method): bool
    {
        $methodLength = strlen($method->name);

        return
            !$method->isStatic() &&
            (
                (0 === strpos($method->name, 'set') && 3 < $methodLength)
                && 2 > $method->getNumberOfRequiredParameters()
            );
    }
}
