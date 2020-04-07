<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess;

use ReflectionClass;
use Symfony\Component\PropertyInfo\Type;

interface ObjectAccessInterface
{
    public function getGetterFields(ReflectionClass $reflectionClass): array;

    public function getSetterFields(ReflectionClass $reflectionClass): array;

    public function getGetterTypes(ReflectionClass $reflectionClass, string $fieldName): array;

    public function getSetterTypes(ReflectionClass $reflectionClass, string $fieldName): array;

    /**
     * @param ReflectionClass $reflectionClass
     * @return Type[]
     */
    public function getConstructorArguments(ReflectionClass $reflectionClass): array;

    public function getValue(object $instance, string $fieldName);

    public function setValue(object $instance, string $fieldName, $value);

    public function instantiate(ReflectionClass $reflectionClass, array $constructorArgs): object;
}
