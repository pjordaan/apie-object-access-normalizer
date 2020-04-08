<?php


namespace W2w\Test\ApieObjectAccessNormalizer\ObjectAccess;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\NameNotFoundException;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\FilteredObjectAccess;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\ObjectAccess;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithSerializationGroup;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\FullRestObject;

class FilteredObjectAccessTest extends TestCase
{
    /**
     * @var FilteredObjectAccess
     */
    private $testItem;

    protected function setUp(): void
    {
        $this->testItem = new FilteredObjectAccess(new ObjectAccess(), ['value1', 'value', 'value3', 'uuid']);
    }

    public function testGetGetterFields()
    {
        $this->assertEquals(
            ['value1', 'value3'],
            $this->testItem->getGetterFields(new ReflectionClass(ClassWithSerializationGroup::class))
        );
        $this->assertEquals(
            ['uuid'],
            $this->testItem->getGetterFields(new ReflectionClass(FullRestObject::class))
        );
    }

    public function testGetSetterFields()
    {
        $this->assertEquals(
            ['value1', 'value3'],
            $this->testItem->getSetterFields(new ReflectionClass(ClassWithSerializationGroup::class))
        );
        $this->assertEquals(
            [],
            $this->testItem->getSetterFields(new ReflectionClass(FullRestObject::class))
        );
    }

    public function testGetGetterTypes()
    {
        $this->assertEquals(
            [new Type(Type::BUILTIN_TYPE_OBJECT, true, ClassWithSerializationGroup::class)],
            $this->testItem->getGetterTypes(new ReflectionClass(ClassWithSerializationGroup::class), 'value1')
        );
    }

    public function testGetGetterTypes_bad_field_name()
    {
        $this->expectException(NameNotFoundException::class);
        $this->testItem->getGetterTypes(new ReflectionClass(ClassWithSerializationGroup::class), 'value2');
    }

    public function testGetSetterTypes()
    {
        $this->assertEquals(
            [new Type(Type::BUILTIN_TYPE_OBJECT, true, ClassWithSerializationGroup::class)],
            $this->testItem->getSetterTypes(new ReflectionClass(ClassWithSerializationGroup::class), 'value1')
        );
    }

    public function testGetSetterTypes_bad_field_name()
    {
        $this->expectException(NameNotFoundException::class);
        $this->testItem->getSetterTypes(new ReflectionClass(ClassWithSerializationGroup::class), 'value2');
    }

    public function testPassthruMethods()
    {
        $this->assertEquals([], $this->testItem->getConstructorArguments(new ReflectionClass(ClassWithSerializationGroup::class)));
        $this->assertEquals(new ClassWithSerializationGroup(), $this->testItem->instantiate(new ReflectionClass(ClassWithSerializationGroup::class), []));
    }

    public function testGetValue_bad_field_name()
    {
        $this->expectException(NameNotFoundException::class);
        $this->testItem->getValue(new ClassWithSerializationGroup(), 'value2');
    }

    public function testSetValue_bad_field_name()
    {
        $this->expectException(NameNotFoundException::class);
        $this->testItem->setValue(new ClassWithSerializationGroup(), 'value2', []);
    }
}
