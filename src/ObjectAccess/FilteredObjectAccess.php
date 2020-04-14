<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess;


use ReflectionClass;
use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\NameNotFoundException;

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

    public function getGetterFields(ReflectionClass $reflectionClass): array
    {
        $result = $this->objectAccess->getGetterFields($reflectionClass);
        return array_values(array_filter($result, function (string $fieldName) {
            return isset($this->filteredFields[$fieldName]);
        }));
    }

    public function getSetterFields(ReflectionClass $reflectionClass): array
    {
        $result = $this->objectAccess->getSetterFields($reflectionClass);
        return array_values(array_filter($result, function (string $fieldName) {
            return isset($this->filteredFields[$fieldName]);
        }));
    }

    public function getGetterTypes(ReflectionClass $reflectionClass, string $fieldName): array
    {
        if (!isset($this->filteredFields[$fieldName])) {
            throw new NameNotFoundException($fieldName);
        }
        return $this->objectAccess->getGetterTypes($reflectionClass, $fieldName);
    }

    public function getSetterTypes(ReflectionClass $reflectionClass, string $fieldName): array
    {
        if (!isset($this->filteredFields[$fieldName])) {
            throw new NameNotFoundException($fieldName);
        }
        return $this->objectAccess->getSetterTypes($reflectionClass, $fieldName);
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @return Type[]
     */
    public function getConstructorArguments(ReflectionClass $reflectionClass): array
    {
        return $this->objectAccess->getConstructorArguments($reflectionClass);
    }

    public function getValue(object $instance, string $fieldName)
    {
        if (!isset($this->filteredFields[$fieldName])) {
            throw new NameNotFoundException($fieldName);
        }
        return $this->objectAccess->getValue($instance, $fieldName);
    }

    public function setValue(object $instance, string $fieldName, $value)
    {
        if (!isset($this->filteredFields[$fieldName])) {
            throw new NameNotFoundException($fieldName);
        }
        return $this->objectAccess->setValue($instance, $fieldName, $value);
    }

    public function instantiate(ReflectionClass $reflectionClass, array $constructorArgs): object
    {
        return $this->objectAccess->instantiate($reflectionClass, $constructorArgs);
    }

    public function getDescription(ReflectionClass $reflectionClass, string $fieldName, bool $preferGetters): ?string
    {
        return $this->objectAccess->getDescription($reflectionClass, $fieldName, $preferGetters);
    }
}
