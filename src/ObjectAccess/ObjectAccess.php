<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Type;
use Throwable;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\CouldNotConvertException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\NameNotFoundException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ObjectAccessException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ObjectWriteException;
use W2w\Lib\ApieObjectAccessNormalizer\Getters\GetterInterface;
use W2w\Lib\ApieObjectAccessNormalizer\Getters\ReflectionMethodGetter;
use W2w\Lib\ApieObjectAccessNormalizer\Getters\ReflectionPropertyGetter;
use W2w\Lib\ApieObjectAccessNormalizer\Interfaces\PriorityAwareInterface;
use W2w\Lib\ApieObjectAccessNormalizer\Setters\ReflectionMethodSetter;
use W2w\Lib\ApieObjectAccessNormalizer\Setters\ReflectionPropertySetter;
use W2w\Lib\ApieObjectAccessNormalizer\Setters\SetterInterface;
use W2w\Lib\ApieObjectAccessNormalizer\TypeUtils;

/**
 * Class that informs about object access and able to access instances of the object in setting/getting values.
 */
class ObjectAccess implements ObjectAccessInterface
{
    /**
     * @var GetterInterface[][][]
     */
    private $getterCache = [];

    /**
     * @var SetterInterface[][][]
     */
    private $setterCache = [];

    /**
     * @var PhpDocExtractor
     */
    private $phpDocExtractor;

    /**
     * @var int
     */
    private $methodFlags;

    /**
     * @var int
     */
    private $propertyFlags;

    /**
     * @var bool
     */
    private $disabledConstructor ;

    public function __construct(bool $publicOnly = true, bool $disabledConstructor = false)
    {
        $this->methodFlags = $publicOnly
            ? ReflectionMethod::IS_PUBLIC
            : (ReflectionMethod::IS_PUBLIC|ReflectionMethod::IS_PROTECTED|ReflectionMethod::IS_PRIVATE);
        $this->propertyFlags = $publicOnly
            ? ReflectionProperty::IS_PUBLIC
            : (ReflectionProperty::IS_PUBLIC|ReflectionProperty::IS_PROTECTED|ReflectionProperty::IS_PRIVATE);
        $this->disabledConstructor = $disabledConstructor;
        $this->phpDocExtractor = new PhpDocExtractor();
    }

    /**
     * Sort getters and setters on priority.
     *
     * @param PriorityAwareInterface[]& $options
     */
    protected function sort(array& $options)
    {
        usort($options, function (PriorityAwareInterface $a, PriorityAwareInterface $b) {
            return $b->getPriority() <=> $a->getPriority();
        });
    }

    /**
     * Added in a method so it can be reused without exposing private property $methodFlags
     * This returns all methods of a class.
     *
     * @param ReflectionClass $reflectionClass
     * @return ReflectionMethod[]
     */
    final protected function getAvailableMethods(ReflectionClass $reflectionClass)
    {
        return $reflectionClass->getMethods($this->methodFlags);
    }

    /**
     * Returns all methods and properties of a class to retrieve a value.
     *
     * @param ReflectionClass $reflectionClass
     * @return GetterInterface[][]
     */
    protected function getGetterMapping(ReflectionClass $reflectionClass): array
    {
        $className= $reflectionClass->getName();
        if (isset($this->getterCache[$className])) {
            return $this->getterCache[$className];
        }

        $attributes = [];

        $reflectionMethods = $this->getAvailableMethods($reflectionClass);
        foreach ($reflectionMethods as $method) {
            if (!TypeUtils::isGetMethod($method)) {
                continue;
            }

            $attributeName = lcfirst(substr($method->name, 0 === strpos($method->name, 'is') ? 2 : 3));
            $method->setAccessible(true);
            $attributes[$attributeName][] = new ReflectionMethodGetter($method);
        }
        $reflectionProperties = $reflectionClass->getProperties($this->propertyFlags);
        foreach ($reflectionProperties as $property) {
            $attributeName = $property->getName();
            $property->setAccessible(true);
            $attributes[$attributeName][] = new ReflectionPropertyGetter($property);
        }
        foreach ($attributes as &$methods) {
            $this->sort($methods);
        }

        return $this->getterCache[$className] = $attributes;
    }

    /**
     * Returns all methods and properties of a class that can set a value.
     *
     * @param ReflectionClass $reflectionClass
     * @return SetterInterface[][]
     */
    protected function getSetterMapping(ReflectionClass $reflectionClass): array
    {
        $className= $reflectionClass->getName();
        if (isset($this->setterCache[$className])) {
            return $this->setterCache[$className];
        }

        $attributes = [];

        $reflectionMethods = $this->getAvailableMethods($reflectionClass);
        foreach ($reflectionMethods as $method) {
            if (!TypeUtils::isSetMethod($method)) {
                continue;
            }

            $attributeName = lcfirst(substr($method->name, 3));
            $method->setAccessible(true);
            $attributes[$attributeName][] = new ReflectionMethodSetter($method);
        }

        $reflectionProperties = $reflectionClass->getProperties($this->propertyFlags);
        foreach ($reflectionProperties as $property) {
            $attributeName = $property->getName();
            $property->setAccessible(true);
            $attributes[$attributeName][] = new ReflectionPropertySetter($property);
        }

        return $this->setterCache[$className] = $attributes;
    }

    /**
     * {@inheritDoc}
     */
    public function getGetterFields(ReflectionClass $reflectionClass): array
    {
        return array_keys($this->getGetterMapping($reflectionClass));
    }

    /**
     * {@inheritDoc}
     */
    public function getSetterFields(ReflectionClass $reflectionClass): array
    {
        return array_keys($this->getSetterMapping($reflectionClass));
    }

    /**
     * {@inheritDoc}
     */
    public function getGetterTypes(ReflectionClass $reflectionClass, string $fieldName): array
    {
        $mapping = $this->getGetterMapping($reflectionClass);
        if (!isset($mapping[$fieldName])) {
            throw new NameNotFoundException($fieldName);
        }
        $res = TypeUtils::convertToTypeArray($mapping[$fieldName]);
        return $this->buildTypes($res, $reflectionClass, $fieldName, 'getSetterMapping');
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(ReflectionClass $reflectionClass, string $fieldName, bool $preferGetters): ?string
    {
        return $this->phpDocExtractor->getShortDescription($reflectionClass->name, $fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function getSetterTypes(ReflectionClass $reflectionClass, string $fieldName): array
    {
        $mapping = $this->getSetterMapping($reflectionClass);
        if (!isset($mapping[$fieldName])) {
            throw new NameNotFoundException($fieldName);
        }
        $res = TypeUtils::convertToTypeArray($mapping[$fieldName]);
        return $this->buildTypes($res, $reflectionClass, $fieldName, 'getGetterMapping');
    }

    /**
     * Sanitize array of Types
     *
     * @param Type[] $types
     * @param ReflectionClass $reflectionClass
     * @param string $fieldName
     * @param string|null $methodOnEmptyResult
     * @return Type[]
     */
    private function buildTypes(array $types, ReflectionClass $reflectionClass, string $fieldName, ?string $methodOnEmptyResult)
    {
        $phpDocTypes = $this->phpDocExtractor->getTypes($reflectionClass->getName(), $fieldName) ?? [];
        foreach ($phpDocTypes as $type) {
            $types[] = $type;
        }
        // fallback checking getters/setters if no typehint was given.
        if (empty($types) && $methodOnEmptyResult) {
            $mapping = $this->$methodOnEmptyResult($reflectionClass);
            if (!isset($mapping[$fieldName])) {
                return [];
            }
            $res = TypeUtils::convertToTypeArray($mapping[$fieldName]);
            return $this->buildTypes($res, $reflectionClass, $fieldName, null);
        }
        return $this->unique($types);
    }

    /**
     * @param Type[] $input
     * @return Type[]
     */
    private function unique(array $input): array
    {
        $res = [];
        foreach ($input as $type) {
            $key = $this->key($type);
            $res[$key] = $type;

        }
        return array_values($res);
    }

    /**
     * Returns a cache key.
     *
     * @param Type $type
     * @param int $recursion
     * @return string
     */
    private function key(Type $type, int $recursion = 0): string
    {
        $data = [
            'built_in' => $type->getBuiltinType(),
            'class_name' => $type->getClassName(),
            'collection' => $type->isCollection(),
            'nullable' => $type->isNullable(),
        ];
        $keyType = $type->getCollectionKeyType();
        if ($keyType && $recursion < 2) {
            $data['key_type'] = $this->key($keyType, $recursion + 1);
        }
        $valueType = $type->getCollectionValueType();
        if ($keyType && $recursion < 2) {
            $data['value_type'] = $this->key($valueType, $recursion + 1);
        }
        return json_encode($data);
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(object $instance, string $fieldName)
    {
        $mapping = $this->getGetterMapping(new ReflectionClass($instance));
        if (!isset($mapping[$fieldName])) {
            throw new NameNotFoundException($fieldName);
        }
        $error = null;
        foreach ($mapping[$fieldName] as $option) {
            try {
                return $option->getValue($instance);
            } catch (Throwable $throwable) {
                $error = new ObjectAccessException($option, $fieldName, $throwable);
                throw $error;
            }
        }
        throw $error ?? new NameNotFoundException($fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function setValue(object $instance, string $fieldName, $value)
    {
        $mapping = $this->getSetterMapping(new ReflectionClass($instance));
        if (!isset($mapping[$fieldName])) {
            throw new NameNotFoundException($fieldName);
        }
        $error = null;
        foreach ($mapping[$fieldName] as $option) {
            try {
                return $option->setValue($instance, $value);
            } catch (Throwable $throwable) {
                $error = new ObjectWriteException($option, $fieldName, $throwable);
                throw $error;
            }
        }
        throw $error ?? new NameNotFoundException($fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function getConstructorArguments(ReflectionClass $reflectionClass): array
    {
        $constructor = $reflectionClass->getConstructor();
        if (!$constructor) {
            return [];
        }
        return $this->getMethodArguments($constructor, $reflectionClass);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodArguments(ReflectionMethod $method, ?ReflectionClass $reflectionClass = null): array
    {
        if (!$reflectionClass) {
            $reflectionClass = $method->getDeclaringClass();
        }
        $res = [];
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type) {
                if ($type->isBuiltin()) {
                    $res[$parameter->name] = new Type($type->getName(), $type->allowsNull());
                } else {
                    $res[$parameter->name] = new Type(Type::BUILTIN_TYPE_OBJECT, $type->allowsNull(), $type->getName());
                }
            } else {
                $res[$parameter->name] = $this->guessType($reflectionClass, $parameter);
            }
        }
        return $res;
    }


    /**
     * Guess the typehint of a constructor argument if it is missing in the constructor.
     *
     * @param ReflectionClass $reflectionClass
     * @param ReflectionParameter $parameter
     * @return Type|null
     */
    private function guessType(ReflectionClass $reflectionClass, ReflectionParameter $parameter): ?Type
    {
        $types = $this->getGetterMapping($reflectionClass)[$parameter->name] ?? [];
        $res = [];
        if (empty($types)) {
            $types = $this->getSetterMapping($reflectionClass)[$parameter->name] ?? [];
            if (empty($types)) {
                return null;
            } else {
                $res = $this->getSetterTypes($reflectionClass, $parameter->name);
            }
        } else {
            $res = $this->getGetterTypes($reflectionClass, $parameter->name);
        }
        return reset($res) ? : null;
    }

    /**
     * {@inheritDoc}
     */
    public function instantiate(ReflectionClass $reflectionClass, array $constructorArgs): object
    {
        if ($this->disabledConstructor) {
            return $reflectionClass->newInstanceWithoutConstructor();
        }
        return $reflectionClass->newInstanceArgs($constructorArgs);
    }
}
