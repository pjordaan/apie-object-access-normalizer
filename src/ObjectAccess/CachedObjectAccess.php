<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess;

use Closure;
use Psr\Cache\CacheItemPoolInterface;
use ReflectionClass;

class CachedObjectAccess implements ObjectAccessSupportedInterface
{
    /**
     * @var ObjectAccessInterface
     */
    private $internal;

    /**
     * @var CacheItemPoolInterface
     */
    private $cacheItemPool;

    /**
     * @param ObjectAccessInterface $internal
     * @param CacheItemPoolInterface $cacheItemPool
     */
    public function __construct(ObjectAccessInterface $internal, CacheItemPoolInterface $cacheItemPool)
    {
        $this->internal = $internal;
        $this->cacheItemPool = $cacheItemPool;
    }

    /**
     * {@inheritDoc}
     */
    public function getGetterFields(ReflectionClass $reflectionClass): array
    {
        return $this->cacheCheck(
            __FUNCTION__ . ',' . $reflectionClass->name,
            function (ReflectionClass $reflectionClass) {
                return $this->internal->getGetterFields($reflectionClass);
            },
            $reflectionClass
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getSetterFields(ReflectionClass $reflectionClass): array
    {
        return $this->cacheCheck(
            __FUNCTION__ . ',' . $reflectionClass->name,
            function (ReflectionClass $reflectionClass) {
                return $this->internal->getSetterFields($reflectionClass);
            },
            $reflectionClass
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getGetterTypes(ReflectionClass $reflectionClass, string $fieldName): array
    {
        return $this->cacheCheck(
            __FUNCTION__ . ',' . $reflectionClass->name . ',' . $fieldName,
            function (ReflectionClass $reflectionClass, string $fieldName) {
                return $this->internal->getGetterTypes($reflectionClass, $fieldName);
            },
            $reflectionClass,
            $fieldName
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getSetterTypes(ReflectionClass $reflectionClass, string $fieldName): array
    {
        return $this->cacheCheck(
            __FUNCTION__ . ',' . $reflectionClass->name . ',' . $fieldName,
            function (ReflectionClass $reflectionClass, string $fieldName) {
                return $this->internal->getSetterTypes($reflectionClass, $fieldName);
            },
            $reflectionClass,
            $fieldName
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getConstructorArguments(ReflectionClass $reflectionClass): array
    {
        return $this->cacheCheck(
            __FUNCTION__ . ',' . $reflectionClass->name,
            function (ReflectionClass $reflectionClass) {
                return $this->internal->getConstructorArguments($reflectionClass);
            },
            $reflectionClass
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(object $instance, string $fieldName)
    {
        return $this->internal->getValue($instance, $fieldName);
    }

    /**
     * {@inheritDoc}
     */
    public function setValue(object $instance, string $fieldName, $value)
    {
        return $this->internal->setValue($instance, $fieldName, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function instantiate(ReflectionClass $reflectionClass, array $constructorArgs): object
    {
        return $this->internal->instantiate($reflectionClass, $constructorArgs);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(ReflectionClass $reflectionClass, string $fieldName, bool $preferGetters): ?string
    {
        return $this->cacheCheck(
            __FUNCTION__ . ',' . $reflectionClass->name . ',' . $fieldName . ',' . json_encode($preferGetters),
            function (ReflectionClass $reflectionClass, string $fieldName, bool $preferGetters) {
                return $this->internal->getDescription($reflectionClass, $fieldName, $preferGetters);
            },
            $reflectionClass,
            $fieldName,
            $preferGetters
        );
    }

    /**
     * {@inheritDoc}
     */
    public function isSupported(ReflectionClass $reflectionClass): bool
    {
        return $this->cacheCheck(
            __FUNCTION__ . ',' . $reflectionClass->name,
            function (ReflectionClass $reflectionClass) {
                if ($this->internal instanceof ObjectAccessSupportedInterface) {
                    return $this->internal->isSupported($reflectionClass);
                }
                return true;
            },
            $reflectionClass
        );
    }

    /**
     * Cache helper method.
     *
     * @param string $cacheKey
     * @param Closure $callback
     * @param mixed ...$args
     * @return mixed
     */
    private function cacheCheck(string $cacheKey, Closure $callback, ...$args)
    {
        $cacheKey = str_replace('\\', '|', $cacheKey);
        $cacheItem = $this->cacheItemPool->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }
        $returnValue = $callback(...$args);
        $cacheItem->set($returnValue);
        $this->cacheItemPool->save($cacheItem);
        return $returnValue;
    }
}
