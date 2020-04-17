<?php


namespace W2w\Test\ApieObjectAccessNormalizer\ObjectAccess;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\GroupedObjectAccess;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\ObjectAccess;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\FullRestObject;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ObjectAccess\MockObjectAccessForSumExample;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\SimplePopo;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\SumExample;

class GroupedObjectAccessTest extends TestCase
{
    /**
     * @dataProvider gettersProvider
     */
    public function testGetters(array $expectedFields, string $className)
    {
        $testItem = new GroupedObjectAccess(
            new ObjectAccess(),
            [
                0 => new MockObjectAccessForSumExample(),
                SimplePopo::class => new ObjectAccess(false, true),
            ]
        );
        $this->assertEquals(
            $expectedFields,
            $testItem->getGetterFields(new ReflectionClass($className))
        );
    }

    public function gettersProvider()
    {
        yield [
            ['uuid', 'stringValue', 'valueObject'],
            FullRestObject::class,
        ];
        yield [
            ['1', '2', '+'],
            SumExample::class,
        ];
        yield [
            ['id', 'createdAt', 'arbitraryField'],
            SimplePopo::class,
        ];
    }

    /**
     * @dataProvider settersProvider
     */
    public function testSetters(array $expectedFields, string $className)
    {
        $testItem = new GroupedObjectAccess(
            new ObjectAccess(),
            [
                0 => new MockObjectAccessForSumExample(),
                SimplePopo::class => new ObjectAccess(false, true),
            ]
        );
        $this->assertEquals(
            $expectedFields,
            $testItem->getSetterFields(new ReflectionClass($className))
        );
    }

    public function settersProvider()
    {
        yield [
            ['stringValue', 'valueObject'],
            FullRestObject::class,
        ];
        yield [
            ['one', 'two'],
            SumExample::class,
        ];
        yield [
            ['id', 'createdAt', 'arbitraryField'],
            SimplePopo::class,
        ];
    }

    /**
     * @dataProvider constructorProvider
     */
    public function testConstructor(array $expectedFields, string $className)
    {
        $testItem = new GroupedObjectAccess(
            new ObjectAccess(),
            [
                0 => new MockObjectAccessForSumExample(),
                SimplePopo::class => new ObjectAccess(false, true),
            ]
        );
        $this->assertEquals(
            $expectedFields,
            $testItem->getConstructorArguments(new ReflectionClass($className))
        );
    }

    public function constructorProvider()
    {
        yield [
            ['uuid' => new Type(Type::BUILTIN_TYPE_OBJECT, true, Uuid::class)],
            FullRestObject::class,
        ];
        yield [
            [
                'one' => new Type(Type::BUILTIN_TYPE_FLOAT, false),
                'two' => new Type(Type::BUILTIN_TYPE_FLOAT, false)
            ],
            SumExample::class,
        ];
        yield [
            [],
            SimplePopo::class,
        ];
    }

    public function testObjectModification()
    {
        $testItem = new GroupedObjectAccess(
            new ObjectAccess(),
            [
                0 => new MockObjectAccessForSumExample(),
                SimplePopo::class => new ObjectAccess(false, true),
            ]
        );
        $actual = $testItem->instantiate(new ReflectionClass(SumExample::class), ['one' => 1, 'two' => 2]);
        $this->assertEquals(new SumExample(1, 2), $actual);
        $testItem->setValue($actual, 'one', 15);
        $this->assertEquals(new SumExample(15, 2), $actual);
        $this->assertEquals(15, $testItem->getValue($actual, '1'));
    }
}
