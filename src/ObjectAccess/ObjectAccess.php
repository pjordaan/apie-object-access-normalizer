<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Type;
use Throwable;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\NameNotFoundException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ObjectAccessException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ObjectWriteException;

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
            if (!$this->isGetMethod($method)) {
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
            if (!$this->isSetMethod($method)) {
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
     * Checks if a method's name is get.* or is.*, and can be called without parameters.
     */
    protected function isGetMethod(\ReflectionMethod $method): bool
    {
        $methodLength = \strlen($method->name);

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
    protected function isSetMethod(\ReflectionMethod $method): bool
    {
        $methodLength = strlen($method->name);

        return
            !$method->isStatic() &&
            (
                (0 === strpos($method->name, 'set') && 3 < $methodLength)
                && 2 > $method->getNumberOfRequiredParameters()
            );
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
        $res = $this->convertToTypeArray($mapping[$fieldName]);
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
        $res = $this->convertToTypeArray($mapping[$fieldName]);
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
            $res = $this->convertToTypeArray($mapping[$fieldName]);
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

    /**
     * @param (ReflectionMethod|ReflectionProperty)[] $methods
     * @return Type[]
     */
    private function convertToTypeArray(array $methods)
    {
        $res = [];
        foreach ($methods as $method) {
            if ($method instanceof ReflectionMethod) {
                $parameters = $method->getParameters();
                $parameter = reset($parameters);
                $type = null;
                if ($parameter && !$parameter->isOptional()) {
                    $type = $parameter->getType();
                } elseif (!$parameter) {
                    $type = $method->getReturnType();
                }
                if (!$type) {
                    continue;
                }
                if ($type->isBuiltin()) {
                    $res[] = new Type($type->getName(), $type->allowsNull());
                } else {
                    $res[] = new Type(Type::BUILTIN_TYPE_OBJECT, $type->allowsNull(), $type->getName());
                }
            } elseif ($method instanceof ReflectionProperty) {
                if (PHP_VERSION_ID >= 70400) {
                    $type = $method->getType();
                    if (!$type) {
                        continue;
                    }
                    if ($type->isBuiltin()) {
                        $res[] = new Type($type->getName(), $type->allowsNull());
                    } else {
                        $res[] = new Type(Type::BUILTIN_TYPE_OBJECT, $type->allowsNull(), $type->getName());
                    }
                }
            }
        }
        return $res;
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
                    $res[$parameter->getName()] = new Type($type->getName(), $type->allowsNull());
                } else {
                    $res[$parameter->getName()] = new Type(Type::BUILTIN_TYPE_OBJECT, $type->allowsNull(), $type->getName());
                }
            } else {
                $types = [];
                if (isset($this->getGetterMapping($reflectionClass)[$parameter->getName()])) {
                    $types = array_merge(
                        $types,
                        $this->getGetterTypes($reflectionClass, $parameter->getName())
                    );
                }
                if (isset($this->getSetterMapping($reflectionClass)[$parameter->getName()])) {
                    $types = array_merge(
                        $types,
                        $this->getSetterTypes($reflectionClass, $parameter->getName())
                    );
                }
                if (empty($types)) {
                    $res[$parameter->getName()] = null;
                } else {
                    $res[$parameter->getName()] = reset($types);
                }
            }
        }
        return $res;
    }

    public function instantiate(ReflectionClass $reflectionClass, array $constructorArgs): object
    {
        return $reflectionClass->newInstanceArgs($constructorArgs);
    }
}
