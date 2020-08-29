<?php


namespace W2w\Lib\ApieObjectAccessNormalizer;

use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\Getters\GetterInterface;
use W2w\Lib\ApieObjectAccessNormalizer\Setters\SetterInterface;

class TypeUtils
{
    /**
     * @param GetterInterface[]|SetterInterface[] $methods
     * @return Type[]
     */
    public static function convertToTypeArray(array $methods)
    {
        $res = [];
        foreach ($methods as $method) {
            $type = $method->toType();
            if ($type) {
                $res[] = $type;
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
        return
            !$method->isStatic()
            && preg_match('/^(get|is|has)[A-Z0-9]/i', $method->name)
            && 0 === $method->getNumberOfRequiredParameters();
    }

    /**
     * Checks if a method's name is set.*  with 0 or 1 parameters.
     */
    public static function isSetMethod(ReflectionMethod $method): bool
    {
        return
            !$method->isStatic()
            && preg_match('/^set[A-Z0-9]/i', $method->name)
            && 2 > $method->getNumberOfRequiredParameters();
    }
}
