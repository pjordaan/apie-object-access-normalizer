<?php


namespace W2w\Test\ApieObjectAccessNormalizer\ObjectAccess;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\SelfObjectAccess;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithSelfObjectAccess;

class SelfObjectAccessTest extends TestCase
{
    public function testAccessWorks()
    {
        $class = new ReflectionClass(ClassWithSelfObjectAccess::class);
        $testItem = new SelfObjectAccess();
        $this->assertTrue($testItem->isSupported($class));
        $this->assertEquals(ClassWithSelfObjectAccess::VALID_KEYS, $testItem->getGetterFields($class));
        $this->assertEquals(ClassWithSelfObjectAccess::VALID_KEYS, $testItem->getSetterFields($class));
        $this->assertEquals(ClassWithSelfObjectAccess::getGetterTypes('one'), $testItem->getGetterTypes($class, 'one'));
        $this->assertEquals(ClassWithSelfObjectAccess::getSetterTypes('one'), $testItem->getSetterTypes($class, 'one'));
        $this->assertEquals(ClassWithSelfObjectAccess::getConstructorArguments(), $testItem->getConstructorArguments($class));
        /** @var ClassWithSelfObjectAccess $actual */
        $actual = $testItem->instantiate($class, ['input' => ['one' => 11, 'two' => 22, 'three' => 33]]);
        $this->assertEquals(11, $testItem->getValue($actual, 'one'));
        $this->assertEquals(22, $testItem->getValue($actual, 'two'));
        $this->assertEquals(33, $testItem->getValue($actual, 'three'));
        $testItem->setValue($actual, 'one', 42);
        $this->assertEquals(42, $testItem->getValue($actual, 'one'));
        $this->assertEquals(42, $actual->getFieldNameValue('one'));
        $this->assertEquals('one', $testItem->getDescription($class, 'one', true));
        $methodArguments = $testItem->getMethodArguments(new ReflectionMethod(ClassWithSelfObjectAccess::class . '::setFieldNameValue'));
        $this->assertEquals(
            [
                'fieldName' => new Type(Type::BUILTIN_TYPE_STRING, false),
                'value' => null,
            ],
            $methodArguments
        );
    }
}
