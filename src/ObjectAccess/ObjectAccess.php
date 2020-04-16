<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Type;
use Throwable;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\NameNotFoundException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ObjectAccessException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ObjectWriteException;
use W2w\Lib\ApieObjectAccessNormalizer\TypeUtils;

class ObjectAccess implements ObjectAccessInterface
{
    private $getterCache = [];

    private $setterCache = [];

    private $phpDocExtractor;

    private $methodFlags;

    private $propertyFlags;

    public function __construct(bool $publicOnly = true)
    {
        $this->methodFlags = $publicOnly
            ? ReflectionMethod::IS_PUBLIC
            : (ReflectionMethod::IS_PUBLIC|ReflectionMethod::IS_PROTECTED|ReflectionMethod::IS_PRIVATE);
        $this->propertyFlags = $publicOnly
            ? ReflectionProperty::IS_PUBLIC
            : (ReflectionProperty::IS_PUBLIC|ReflectionProperty::IS_PROTECTED|ReflectionProperty::IS_PRIVATE);
        $this->phpDocExtractor = new PhpDocExtractor();
    }

    private function sort(array& $options)
    {
        usort($options, function ($a, $b) {
            if ($a instanceof ReflectionProperty) {
                return 1;
            }
            if ($b instanceof ReflectionProperty) {
                return -1;
            }
            /** @var ReflectionMethod $a */
            /** @var ReflectionMethod $b */
            // prio: get, is, has:
            if (strpos($a->getName(), 'get') === 0) {
                return -1;
            }
            if (strpos($b->getName(), 'get') === 0) {
                return 1;
            }
            if (strpos($a->getName(), 'is') === 0) {
                return -1;
            }
            return 1;
        });
    }

    protected function getGetterMapping(ReflectionClass $reflectionClass): array
    {
        $className= $reflectionClass->getName();
        if (isset($this->getterCache[$className])) {
            return $this->getterCache[$className];
        }

        $attributes = [];

        $reflectionMethods = $reflectionClass->getMethods($this->methodFlags);
        foreach ($reflectionMethods as $method) {
            if (!TypeUtils::isGetMethod($method)) {
                continue;
            }

            $attributeName = lcfirst(substr($method->name, 0 === strpos($method->name, 'is') ? 2 : 3));
            $method->setAccessible(true);
            $attributes[$attributeName][] = $method;
        }
        $reflectionProperties = $reflectionClass->getProperties($this->propertyFlags);
        foreach ($reflectionProperties as $property) {
            $attributeName = $property->getName();
            $property->setAccessible(true);
            $attributes[$attributeName][] = $property;
        }
        foreach ($attributes as &$methods) {
            $this->sort($methods);
        }

        return $this->getterCache[$className] = $attributes;
    }

    protected function getSetterMapping(ReflectionClass $reflectionClass): array
    {
        $className= $reflectionClass->getName();
        if (isset($this->setterCache[$className])) {
            return $this->setterCache[$className];
        }

        $attributes = [];

        $reflectionMethods = $reflectionClass->getMethods($this->methodFlags);
        foreach ($reflectionMethods as $method) {
            if (!TypeUtils::isSetMethod($method)) {
                continue;
            }

            $attributeName = lcfirst(substr($method->name, 3));
            $method->setAccessible(true);
            $attributes[$attributeName][] = $method;
        }

        $reflectionProperties = $reflectionClass->getProperties($this->propertyFlags);
        foreach ($reflectionProperties as $property) {
            $attributeName = $property->getName();
            $property->setAccessible(true);
            $attributes[$attributeName][] = $property;
        }

        return $this->setterCache[$className] = $attributes;
    }

    public function getGetterFields(ReflectionClass $reflectionClass): array
    {
        return array_keys($this->getGetterMapping($reflectionClass));
    }

    public function getSetterFields(ReflectionClass $reflectionClass): array
    {
        return array_keys($this->getSetterMapping($reflectionClass));
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @param string $fieldName
     * @return Type[]
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
     * Returns description of a field name.
     *
     * @TODO: make a difference between getters and setters
     *
     * @param ReflectionClass $reflectionClass
     * @param string $fieldName
     * @param bool $preferGetters
     * @return string|null
     */
    public function getDescription(ReflectionClass $reflectionClass, string $fieldName, bool $preferGetters): ?string
    {
        return $this->phpDocExtractor->getShortDescription($reflectionClass->name, $fieldName);
    }

    public function getSetterTypes(ReflectionClass $reflectionClass, string $fieldName): array
    {
        $mapping = $this->getSetterMapping($reflectionClass);
        if (!isset($mapping[$fieldName])) {
            throw new NameNotFoundException($fieldName);
        }
        $res = TypeUtils::convertToTypeArray($mapping[$fieldName]);
        return $this->buildTypes($res, $reflectionClass, $fieldName, 'getGetterMapping');
    }

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

    public function getValue(object $instance, string $fieldName)
    {
        $mapping = $this->getGetterMapping(new ReflectionClass($instance));
        if (!isset($mapping[$fieldName])) {
            throw new NameNotFoundException($fieldName);
        }
        $error = null;
        foreach ($mapping[$fieldName] as $option) {
            if ($option instanceof ReflectionMethod) {
                try {
                    return $option->invoke($instance);
                } catch (Throwable $throwable) {
                    $error = new ObjectAccessException($option, $fieldName, $throwable);
                }
            }
            if ($option instanceof ReflectionProperty) {
                if ($error) {
                    throw $error;
                }
                return $option->getValue($instance);
            }
        }
        throw $error ?? new NameNotFoundException($fieldName);
    }

    public function setValue(object $instance, string $fieldName, $value)
    {
        $mapping = $this->getSetterMapping(new ReflectionClass($instance));
        if (!isset($mapping[$fieldName])) {
            throw new NameNotFoundException($fieldName);
        }
        $error = null;
        foreach ($mapping[$fieldName] as $option) {
            if ($option instanceof ReflectionMethod) {
                try {
                    return $option->invoke($instance, $value);
                } catch (Throwable $throwable) {
                    $error = new ObjectWriteException($option, $fieldName, $throwable);
                }
            }
            if ($option instanceof ReflectionProperty) {
                if ($error) {
                    throw $error;
                }
                try {
                    return $option->setValue($instance, $value);
                } catch (Throwable $throwable) {
                    $error = new ObjectWriteException($option, $fieldName, $throwable);
                }
            }
        }
        throw $error ?? new NameNotFoundException($fieldName);
    }

    public function getConstructorArguments(ReflectionClass $reflectionClass): array
    {
        $constructor = $reflectionClass->getConstructor();
        if (!$constructor) {
            return [];
        }
        $res = [];
        foreach ($constructor->getParameters() as $parameter) {
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

    public function instantiate(ReflectionClass $reflectionClass, array $constructorArgs): object
    {
        return $reflectionClass->newInstanceArgs($constructorArgs);
    }
}
