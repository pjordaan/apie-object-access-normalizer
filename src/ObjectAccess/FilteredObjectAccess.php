<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess;

use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\NameNotFoundException;
use W2w\Lib\ApieObjectAccessNormalizer\Interfaces\ObjectAccessWithNotFilterablePropertiesInterface;

/**
 * Decorator around an Object Access to filter the properties that are accessible.
 */
class FilteredObjectAccess implements ObjectAccessInterface
{
    /**
     * @var ObjectAccessInterface
     */
    private $objectAccess;
    /**
     * @var string[]
     */
    private $filteredFields;

    /**
     * @param ObjectAccessInterface $objectAccess
     * @param string[] $filteredFields
     */
    public function __construct(ObjectAccessInterface $objectAccess, array $filteredFields)
    {
        $this->objectAccess = $objectAccess;
        $this->filteredFields = array_combine($filteredFields, $filteredFields);
    }

    /**
     * {@inheritDoc}
     */
    public function getGetterFields(ReflectionClass $reflectionClass): array
    {
        $result = $this->objectAccess->getGetterFields($reflectionClass);
        $notFilterableProperties = [];
        if ($this->objectAccess instanceof ObjectAccessWithNotFilterablePropertiesInterface) {
            $notFilterableProperties = array_fill_keys($this->objectAccess->getNotFilterableProperties(), 1);
        }
        return array_values(array_filter($result, function (string $fieldName) use (&$notFilterableProperties) {
            return isset($this->filteredFields[$fieldName]) || isset($notFilterableProperties[$fieldName]);
        }));
    }

    /**
     * {@inheritDoc}
     */
    public function getSetterFields(ReflectionClass $reflectionClass): array
    {
        $result = $this->objectAccess->getSetterFields($reflectionClass);
        return array_values(array_filter($result, function (string $fieldName) {
            return isset($this->filteredFields[$fieldName]);
        }));
    }


    /**
     * {@inheritDoc}
     */
    public function getGetterTypes(ReflectionClass $reflectionClass, string $fieldName): array
    {
        if (!isset($this->filteredFields[$fieldName])) {
            throw new NameNotFoundException($fieldName);
        }
        return $this->objectAccess->getGetterTypes($reflectionClass, $fieldName);
    }


    /**
     * {@inheritDoc}
     */
    public function getSetterTypes(ReflectionClass $reflectionClass, string $fieldName): array
    {
        if (!isset($this->filteredFields[$fieldName])) {
            throw new NameNotFoundException($fieldName);
        }
        return $this->objectAccess->getSetterTypes($reflectionClass, $fieldName);
    }


    /**
     * {@inheritDoc}
     */
    public function getConstructorArguments(ReflectionClass $reflectionClass): array
    {
        return $this->objectAccess->getConstructorArguments($reflectionClass);
    }


    /**
     * {@inheritDoc}
     */
    public function getValue(object $instance, string $fieldName)
    {
        $notFilterableProperties = [];
        if ($this->objectAccess instanceof ObjectAccessWithNotFilterablePropertiesInterface) {
            $notFilterableProperties = array_fill_keys($this->objectAccess->getNotFilterableProperties(), 1);
        }
        if (!isset($this->filteredFields[$fieldName]) && !isset($notFilterableProperties[$fieldName])) {
            throw new NameNotFoundException($fieldName);
        }
        return $this->objectAccess->getValue($instance, $fieldName);
    }


    /**
     * {@inheritDoc}
     */
    public function setValue(object $instance, string $fieldName, $value)
    {
        $notFilterableProperties = [];
        if ($this->objectAccess instanceof ObjectAccessWithNotFilterablePropertiesInterface) {
            $notFilterableProperties = array_fill_keys($this->objectAccess->getNotFilterableProperties(), 1);
        }
        if (!isset($this->filteredFields[$fieldName]) && !isset($notFilterableProperties[$fieldName])) {
            throw new NameNotFoundException($fieldName);
        }
        return $this->objectAccess->setValue($instance, $fieldName, $value);
    }


    /**
     * {@inheritDoc}
     */
    public function instantiate(ReflectionClass $reflectionClass, array $constructorArgs): object
    {
        return $this->objectAccess->instantiate($reflectionClass, $constructorArgs);
    }


    /**
     * {@inheritDoc}
     */
    public function getDescription(ReflectionClass $reflectionClass, string $fieldName, bool $preferGetters): ?string
    {
        return $this->objectAccess->getDescription($reflectionClass, $fieldName, $preferGetters);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodArguments(ReflectionMethod $method, ?ReflectionClass $reflectionClass = null): array
    {
        return $this->getMethodArguments($method, $reflectionClass);
    }
}
