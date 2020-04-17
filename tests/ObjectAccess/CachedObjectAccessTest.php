<?php

namespace W2w\Test\ApieObjectAccessNormalizer\ObjectAccess;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\CachedObjectAccess;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\ObjectAccess;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\FullRestObject;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\SumExample;

class CachedObjectAccessTest extends TestCase
{
    /**
     * @var ArrayAdapter
     */
    private $cache;

    /**
     * @var CachedObjectAccess
     */
    private $testItem;

    /**
     * @var int|null
     */
    private $value;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->testItem = new CachedObjectAccess(new ObjectAccess(), $this->cache);
    }

    public function testGetGetterFields()
    {
        $cacheKey = str_replace('\\', '|', 'getGetterFields,' . SumExample::class);
        $this->assertEquals(['addition'], $this->testItem->getGetterFields(new ReflectionClass(SumExample::class)));
        $cacheItem = $this->cache->getItem($cacheKey);
        $this->assertTrue($cacheItem->isHit(), 'cachekey "' . $cacheKey . '" not found, got ' . $this->getCacheItems());
        $this->assertEquals(['addition'], $this->cache->getItem($cacheKey)->get());
        $this->assertEquals(['addition'], $this->testItem->getGetterFields(new ReflectionClass(SumExample::class)));
    }

    public function testGetGetterTypes()
    {
        $expected = [new Type(Type::BUILTIN_TYPE_FLOAT, false)];
        $cacheKey = str_replace('\\', '|', 'getGetterTypes,' . SumExample::class . ',addition');
        $this->assertEquals($expected, $this->testItem->getGetterTypes(new ReflectionClass(SumExample::class), 'addition'));
        $cacheItem = $this->cache->getItem($cacheKey);
        $this->assertTrue($cacheItem->isHit(), 'cachekey "' . $cacheKey . '" not found, got ' . $this->getCacheItems());
        $this->assertEquals($expected, $this->cache->getItem($cacheKey)->get());
        $this->assertEquals($expected, $this->testItem->getGetterTypes(new ReflectionClass(SumExample::class), 'addition'));
    }

    public function testGetSetterFields()
    {
        $expected = [];
        $cacheKey = str_replace('\\', '|', 'getSetterFields,' . SumExample::class);
        $this->assertEquals($expected, $this->testItem->getSetterFields(new ReflectionClass(SumExample::class)));
        $cacheItem = $this->cache->getItem($cacheKey);
        $this->assertTrue($cacheItem->isHit(), 'cachekey "' . $cacheKey . '" not found, got ' . $this->getCacheItems());
        $this->assertEquals($expected, $this->cache->getItem($cacheKey)->get());
        $this->assertEquals($expected, $this->testItem->getSetterFields(new ReflectionClass(SumExample::class)));
    }

    public function testGetSetterTypes()
    {
        $expected = [new Type(Type::BUILTIN_TYPE_STRING, false)];
        $cacheKey = str_replace('\\', '|', 'getSetterTypes,' . FullRestObject::class . ',stringValue');
        $this->assertEquals($expected, $this->testItem->getSetterTypes(new ReflectionClass(FullRestObject::class), 'stringValue'));
        $cacheItem = $this->cache->getItem($cacheKey);
        $this->assertTrue($cacheItem->isHit(), 'cachekey "' . $cacheKey . '" not found, got ' . $this->getCacheItems());
        $this->assertEquals($expected, $this->cache->getItem($cacheKey)->get());
        $this->assertEquals($expected, $this->testItem->getSetterTypes(new ReflectionClass(FullRestObject::class), 'stringValue'));
    }

    public function testGetConstructorArguments()
    {
        $expected = [
            'one' => new Type(Type::BUILTIN_TYPE_FLOAT, false),
            'two' => new Type(Type::BUILTIN_TYPE_FLOAT, false),
        ];
        $cacheKey = str_replace('\\', '|', 'getConstructorArguments,' . SumExample::class);
        $this->assertEquals($expected, $this->testItem->getConstructorArguments(new ReflectionClass(SumExample::class)));
        $cacheItem = $this->cache->getItem($cacheKey);
        $this->assertTrue($cacheItem->isHit(), 'cachekey "' . $cacheKey . '" not found, got ' . $this->getCacheItems());
        $this->assertEquals($expected, $this->cache->getItem($cacheKey)->get());
        $this->assertEquals($expected, $this->testItem->getConstructorArguments(new ReflectionClass(SumExample::class)));
    }

    public function testIsSupported()
    {
        $expected = true;
        $cacheKey = str_replace('\\', '|', 'isSupported,' . SumExample::class);
        $this->assertEquals($expected, $this->testItem->isSupported(new ReflectionClass(SumExample::class)));
        $cacheItem = $this->cache->getItem($cacheKey);
        $this->assertTrue($cacheItem->isHit(), 'cachekey "' . $cacheKey . '" not found, got ' . $this->getCacheItems());
        $this->assertEquals($expected, $this->cache->getItem($cacheKey)->get());
        $this->assertEquals($expected, $this->testItem->isSupported(new ReflectionClass(SumExample::class)));
    }

    public function testGetDescription()
    {
        $expected = 'First number';
        $cacheKey = str_replace('\\', '|', 'getDescription,' . SumExample::class . ',one,true');
        $this->assertEquals($expected, $this->testItem->getDescription(new ReflectionClass(SumExample::class), 'one', true));
        $cacheItem = $this->cache->getItem($cacheKey);
        $this->assertTrue($cacheItem->isHit(), 'cachekey "' . $cacheKey . '" not found, got ' . $this->getCacheItems());
        $this->assertEquals($expected, $this->cache->getItem($cacheKey)->get());
        $this->assertEquals($expected, $this->testItem->getDescription(new ReflectionClass(SumExample::class), 'one', true));
    }

    public function testStandardPassthruMethods()
    {
        $actual = $this->testItem->instantiate(new ReflectionClass(SumExample::class), [1, '2']);
        $this->assertEquals(new SumExample(1, 2), $actual);
        $this->assertEquals(3, $this->testItem->getValue($actual, 'addition'));
        $this->testItem->setValue($this, 'over9000', '9001');
        $this->assertEquals(9001, $this->value);
    }

    public function setOver9000(int $value)
    {
        if ($value <= 9000) {
            throw new RuntimeException("It's not over 9000!");
        }
        $this->value = $value;
    }

    private function getCacheItems(): string
    {
        return implode(
            ', ',
            array_map(
                function (CacheItemInterface $cacheItem) {
                    return $cacheItem->getKey();
                },
                iterator_to_array($this->cache->getItems())
            )
        );
    }
}
