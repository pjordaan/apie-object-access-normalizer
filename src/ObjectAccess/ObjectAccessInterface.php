<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess;

use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\PropertyInfo\Type;

interface ObjectAccessInterface
{
    /**
     * Returns all property names that can be retrieved.
     *
     * @param ReflectionClass $reflectionClass
     * @return string[]
     */
    public function getGetterFields(ReflectionClass $reflectionClass): array;

    /**
     * Returns all property names that can be changed.
     *
     * @param ReflectionClass $reflectionClass
     * @return string[]
     */
    public function getSetterFields(ReflectionClass $reflectionClass): array;

    /**
     * Returns all types that can be returned.
     *
     * @param ReflectionClass $reflectionClass
     * @param string $fieldName
     * @return Type[]
     */
    public function getGetterTypes(ReflectionClass $reflectionClass, string $fieldName): array;

    /**
     * Returns all types that can be used for changing a value.
     *
     * @param ReflectionClass $reflectionClass
     * @param string $fieldName
     * @return array
     */
    public function getSetterTypes(ReflectionClass $reflectionClass, string $fieldName): array;

    /**
     * Returns all types to instantiate an object.
     *
     * @param ReflectionClass $reflectionClass
     * @return Type[]
     */
    public function getConstructorArguments(ReflectionClass $reflectionClass): array;

    /**
     * Returns all types to call a method.
     *
     * @param ReflectionMethod $method
     * @param ReflectionClass|null $reflectionClass
     * @return Type[]
     */
    public function getMethodArguments(ReflectionMethod $method, ?ReflectionClass $reflectionClass = null): array;

    /**
     * Gets a property value.
     *
     * @param object $instance
     * @param string $fieldName
     * @return mixed
     */
    public function getValue(object $instance, string $fieldName);

    /**
     * Sets a property instance value.
     *
     * @param object $instance
     * @param string $fieldName
     * @param mixed $value
     * @return mixed
     */
    public function setValue(object $instance, string $fieldName, $value);

    /**
     * Instantiate the object with the arguments provided here.
     *
     * @param ReflectionClass $reflectionClass
     * @param array $constructorArgs
     * @return object
     */
    public function instantiate(ReflectionClass $reflectionClass, array $constructorArgs): object;

    /**
     * Returns a string description of the property.
     *
     * @param ReflectionClass $reflectionClass
     * @param string $fieldName
     * @param bool $preferGetters
     * @return string|null
     */
    public function getDescription(ReflectionClass $reflectionClass, string $fieldName, bool $preferGetters): ?string;
}
