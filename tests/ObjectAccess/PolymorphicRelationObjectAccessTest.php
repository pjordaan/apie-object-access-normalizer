<?php

namespace W2w\Test\ApieObjectAccessNormalizer\ObjectAccess;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\PolymorphicRelationObjectAccess;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\Polymorphic\BaseClass;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\Polymorphic\MutablePizza;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\Polymorphic\SalamiPizza;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\Polymorphic\ValueObjectPizza;

class PolymorphicRelationObjectAccessTest extends TestCase
{
    private $testItem;

    protected function setUp(): void
    {
        $this->testItem = new PolymorphicRelationObjectAccess(
            BaseClass::class,
            [
                'mutable' => MutablePizza::class,
                'salami' => SalamiPizza::class,
                'valued' => ValueObjectPizza::class,
            ],
            'pizzaType'
        );
    }

    /**
     * @dataProvider getConstructorArgumentsProvider
     */
    public function testGetConstructorArguments(array $expected, string $className, array $data = [])
    {
        $actual = $this->testItem->getConstructorArguments(new ReflectionClass($className), $data);
        $this->assertEquals($expected, $actual);
    }

    public function getConstructorArgumentsProvider()
    {
        yield [['pizzaType' => new Type(Type::BUILTIN_TYPE_STRING)], BaseClass::class];
        yield [['pizzaType' => new Type(Type::BUILTIN_TYPE_STRING)], MutablePizza::class];
        yield [['pizzaType' => new Type(Type::BUILTIN_TYPE_STRING)], SalamiPizza::class];
        yield [
            ['pizzaType' => new Type(Type::BUILTIN_TYPE_STRING), 'pizza' => new Type(Type::BUILTIN_TYPE_STRING)],
            ValueObjectPizza::class
        ];
    }

    /**
     * @dataProvider instantiateProvider
     */
    public function testInstantiate(BaseClass $expected, string $className, array $data = [])
    {
        $actual = $this->testItem->instantiate(new ReflectionClass($className), $data);
        $this->assertEquals($expected, $actual);
    }

    public function instantiateProvider()
    {
        yield [
            new MutablePizza(),
            BaseClass::class,
            ['pizzaType' => 'mutable'],
        ];
        yield [
            new MutablePizza(),
            MutablePizza::class,
            ['pizzaType' => 'mutable'],
        ];
        yield [
            new SalamiPizza(),
            BaseClass::class,
            ['pizzaType' => 'salami'],
        ];
        yield [
            new SalamiPizza(),
            SalamiPizza::class,
            ['pizzaType' => 'salami'],
        ];
    }
}
