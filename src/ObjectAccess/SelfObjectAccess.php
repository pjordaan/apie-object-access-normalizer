<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess;

use ReflectionClass;
use ReflectionMethod;

/**
 * ObjectAccess instance that only works on objects that implement SelfObjectAccessInterface
 *
 * @see SelfObjectAccessInterface
 */
class SelfObjectAccess implements ObjectAccessSupportedInterface
{
    /**
     * {@inheritDoc}
     */
    public function getGetterFields(ReflectionClass $reflectionClass): array
    {
        $method = new ReflectionMethod($reflectionClass->getName(), __FUNCTION__);
        return $method->invoke(null);
    }

    /**
     * {@inheritDoc}
     */
    public function getSetterFields(ReflectionClass $reflectionClass): array
    {
        $method = new ReflectionMethod($reflectionClass->getName(), __FUNCTION__);
        return $method->invoke(null);
    }

    /**
     * {@inheritDoc}
     */
    public function getGetterTypes(ReflectionClass $reflectionClass, string $fieldName): array
    {
        $method = new ReflectionMethod($reflectionClass->getName(), __FUNCTION__);
        return $method->invoke(null, $fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function getSetterTypes(ReflectionClass $reflectionClass, string $fieldName): array
    {
        $method = new ReflectionMethod($reflectionClass->getName(), __FUNCTION__);
        return $method->invoke(null, $fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function getConstructorArguments(ReflectionClass $reflectionClass): array
    {
        $method = new ReflectionMethod($reflectionClass->getName(), __FUNCTION__);
        return $method->invoke(null);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodArguments(ReflectionMethod $method, ?ReflectionClass $reflectionClass = null): array
    {
        return (new ObjectAccess())->getMethodArguments($method, $reflectionClass);
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(object $instance, string $fieldName)
    {
        $method = new ReflectionMethod($instance, 'getFieldNameValue');
        return $method->invoke($instance, $fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function setValue(object $instance, string $fieldName, $value)
    {
        $method = new ReflectionMethod($instance, 'setFieldNameValue');
        return $method->invoke($instance, $fieldName, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function instantiate(ReflectionClass $reflectionClass, array $constructorArgs): object
    {
        $method = new ReflectionMethod($reflectionClass->getName(), __FUNCTION__);
        return $method->invoke(null, $constructorArgs);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(ReflectionClass $reflectionClass, string $fieldName, bool $preferGetters): ?string
    {
        $method = new ReflectionMethod($reflectionClass->getName(), __FUNCTION__);
        return $method->invoke(null, $fieldName, $preferGetters);
    }

    /**
     * Returns true if class is supported for this ObjectAccess instance.
     *
     * @param ReflectionClass $reflectionClass
     * @return bool
     */
    public function isSupported(ReflectionClass $reflectionClass): bool
    {
        return $reflectionClass->implementsInterface(SelfObjectAccessInterface::class);
    }
}
