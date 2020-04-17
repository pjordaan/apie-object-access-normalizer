<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess;

use ReflectionClass;

class GroupedObjectAccess implements ObjectAccessSupportedInterface
{
    /**
     * @var ObjectAccessInterface
     */
    private $fallback;

    /**
     * @var ObjectAccessInterface[]
     */
    private $specificObjectAccess;

    /**
     * @param ObjectAccessInterface $fallback
     * @param ObjectAccessInterface[] $specificObjectAccess
     */
    public function __construct(ObjectAccessInterface $fallback, array $specificObjectAccess)
    {
        $this->fallback = $fallback;
        $this->specificObjectAccess = $specificObjectAccess;
    }

    /**
     * Returns the correct ObjectAccessInterface instance.
     *
     * @param ReflectionClass $reflectionClass
     * @return ObjectAccessInterface
     */
    private function findObjectAccessForClass(ReflectionClass $reflectionClass): ObjectAccessInterface
    {
        if (isset($this->specificObjectAccess[$reflectionClass->name])) {
            return $this->specificObjectAccess[$reflectionClass->name];
        }
        foreach ($this->specificObjectAccess as $key => $objectAccess) {
            if ($objectAccess instanceof ObjectAccessSupportedInterface && $objectAccess->isSupported($reflectionClass)) {
                return $objectAccess;
            }
            if (is_a($reflectionClass->name, $key, true)) {
                return $objectAccess;
            }
        }
        return $this->fallback;
    }

    /**
     * {@inheritDoc}
     */
    public function getGetterFields(ReflectionClass $reflectionClass): array
    {
        return $this->findObjectAccessForClass($reflectionClass)->getGetterFields($reflectionClass);
    }

    /**
     * {@inheritDoc}
     */
    public function getSetterFields(ReflectionClass $reflectionClass): array
    {
        return $this->findObjectAccessForClass($reflectionClass)->getSetterFields($reflectionClass);
    }

    /**
     * {@inheritDoc}
     */
    public function getGetterTypes(ReflectionClass $reflectionClass, string $fieldName): array
    {
        return $this->findObjectAccessForClass($reflectionClass)->getGetterTypes($reflectionClass, $fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function getSetterTypes(ReflectionClass $reflectionClass, string $fieldName): array
    {
        return $this->findObjectAccessForClass($reflectionClass)->getSetterTypes($reflectionClass, $fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function getConstructorArguments(ReflectionClass $reflectionClass): array
    {
        return $this->findObjectAccessForClass($reflectionClass)->getConstructorArguments($reflectionClass);
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(object $instance, string $fieldName)
    {
        return $this->findObjectAccessForClass(new ReflectionClass($instance))->getValue($instance, $fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function setValue(object $instance, string $fieldName, $value)
    {
        return $this->findObjectAccessForClass(new ReflectionClass($instance))->setValue($instance, $fieldName, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function instantiate(ReflectionClass $reflectionClass, array $constructorArgs): object
    {
        return $this->findObjectAccessForClass($reflectionClass)->instantiate($reflectionClass, $constructorArgs);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(ReflectionClass $reflectionClass, string $fieldName, bool $preferGetters): ?string
    {
        return $this->findObjectAccessForClass($reflectionClass)->getDescription($reflectionClass, $fieldName, $preferGetters);
    }

    /**
     * {@inheritDoc}
     */
    public function isSupported(ReflectionClass $reflectionClass): bool
    {
        $instance = $this->findObjectAccessForClass($reflectionClass);
        if ($instance instanceof ObjectAccessSupportedInterface) {
            return $instance->isSupported($reflectionClass);
        }
        return true;
    }
}
