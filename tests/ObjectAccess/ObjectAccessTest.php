<?php

namespace W2w\Test\ApieObjectAccessNormalizer\ObjectAccess;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use ReflectionClass;
use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\NameNotFoundException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ObjectAccessException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ObjectWriteException;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\ObjectAccess;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithConflictingTypehints;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithGetterErrorAndPublicProperty;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithGetterWithErrorAndNoConstructor;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithManyTypehintsAndSetter;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithMultipleTypes;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithNoTypehints;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithoutConstructorTypehint;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithPhp74PropertyTypehint;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithSubclass;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithValueObject;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\FullRestObject;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\SimplePopo;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\SumExample;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ValueObject;

class ObjectAccessTest extends TestCase
{
    /**
     * @dataProvider getGetterFieldsProvider
     */
    public function testGetGetterFields(array $expectedGetterFields, array $expectedSetterFields, string $className)
    {
        $testItem = new ObjectAccess();
        $this->assertEquals(
            $expectedGetterFields,
            $testItem->getGetterFields(new ReflectionClass($className)),
            'getter fields should match with expected'
        );
        $this->assertEquals(
            $expectedSetterFields,
            $testItem->getSetterFields(new ReflectionClass($className)),
            'setter fields should match with expected'
        );
    }

    public function getGetterFieldsProvider()
    {
        yield [
            ['uuid', 'stringValue', 'valueObject'],
            ['stringValue', 'valueObject'],
            FullRestObject::class,
        ];
        yield [
            ['boolean'],
            ['boolean'],
            ClassWithConflictingTypehints::class,
        ];
        yield [
            ['pizza'],
            [],
            ClassWithGetterWithErrorAndNoConstructor::class,
        ];
        yield [
            ['boolean', 'createdAt', 'updatedAt', 'string'],
            ['boolean', 'string'],
            ClassWithMultipleTypes::class,
        ];
        yield [
            ['noTypehint', 'alsoNoTypehint'],
            ['noTypehint', 'alsoNoTypehint'],
            ClassWithNoTypehints::class,
        ];
        yield [
            ['input'],
            ['input'],
            ClassWithoutConstructorTypehint::class,
        ];
        yield [
            ['subClass'],
            ['subClass'],
            ClassWithSubclass::class,
        ];
        yield [
            ['valueObject', 'uuid'],
            ['valueObject'],
            ClassWithValueObject::class,
        ];
        yield [
            ['id', 'createdAt', 'arbitraryField'],
            ['arbitraryField'],
            SimplePopo::class,
        ];
        yield [
            ['addition'],
            [],
            SumExample::class,
        ];
        yield [
            [],
            [],
            ValueObject::class,
        ];
    }

    /**
     * @dataProvider getGetterFieldsPrivateProvider
     */
    public function testGetGetterFields_private_fields(array $expectedGetterFields, array $expectedSetterFields, string $className)
    {
        $testItem = new ObjectAccess(false);
        $this->assertEquals(
            $expectedGetterFields,
            $testItem->getGetterFields(new ReflectionClass($className)),
            'getter fields should match with expected'
        );
        $this->assertEquals(
            $expectedSetterFields,
            $testItem->getSetterFields(new ReflectionClass($className)),
            'setter fields should match with expected'
        );
    }

    public function getGetterFieldsPrivateProvider()
    {
        yield [
            ['uuid', 'stringValue', 'valueObject'],
            ['uuid', 'stringValue', 'valueObject'],
            FullRestObject::class,
        ];
        yield [
            ['boolean'],
            ['boolean'],
            ClassWithConflictingTypehints::class,
        ];
        yield [
            ['pizza'],
            [],
            ClassWithGetterWithErrorAndNoConstructor::class,
        ];
        yield [
            ['boolean', 'createdAt', 'updatedAt', 'string'],
            ['boolean', 'string', 'createdAt', 'updatedAt'],
            ClassWithMultipleTypes::class,
        ];
        yield [
            ['noTypehint', 'alsoNoTypehint'],
            ['noTypehint', 'alsoNoTypehint'],
            ClassWithNoTypehints::class,
        ];
        yield [
            ['input'],
            ['input'],
            ClassWithoutConstructorTypehint::class,
        ];
        yield [
            ['subClass'],
            ['subClass'],
            ClassWithSubclass::class,
        ];
        yield [
            ['valueObject', 'uuid'],
            ['valueObject', 'uuid'],
            ClassWithValueObject::class,
        ];
        yield [
            ['id', 'createdAt', 'arbitraryField'],
            ['id', 'createdAt', 'arbitraryField'],
            SimplePopo::class,
        ];
        yield [
            ['addition', 'one', 'two'],
            ['one', 'two'],
            SumExample::class,
        ];
        yield [
            [],
            [],
            ValueObject::class,
        ];
    }

    /**
     * @dataProvider getConstructorArgumentsProvider
     */
    public function testGetConstructorArguments(array $expected, string $className)
    {
        $testItem = new ObjectAccess();
        $actual = $testItem->getConstructorArguments(new ReflectionClass($className));
        $this->assertEquals(
            $expected,
            $actual
        );
        $this->assertEquals(
            array_keys($expected),
            array_keys($actual)
        );
    }

    public function getConstructorArgumentsProvider()
    {
        yield [
            ['boolean' => new Type(Type::BUILTIN_TYPE_FLOAT, false)],
            ClassWithConflictingTypehints::class,
        ];
        yield [
            [],
            ClassWithGetterWithErrorAndNoConstructor::class,
        ];
        yield [
            [
                'boolean' => new Type(Type::BUILTIN_TYPE_BOOL, false),
                'string' => new Type(Type::BUILTIN_TYPE_STRING, false),
            ],
            ClassWithMultipleTypes::class,
        ];
        yield [
            [
                'noTypehint' => null,
            ],
            ClassWithNoTypehints::class
        ];
        yield [
            [
                'input' => new Type(Type::BUILTIN_TYPE_STRING, false),
            ],
            ClassWithoutConstructorTypehint::class,
        ];
        yield [
            [
                'subClass' => new Type(Type::BUILTIN_TYPE_OBJECT, false, ClassWithoutConstructorTypehint::class)
            ],
            ClassWithSubclass::class,
        ];
        yield [
            [
                'uuid' => new Type(Type::BUILTIN_TYPE_OBJECT, false, Uuid::class)
            ],
            ClassWithValueObject::class,
        ];
        yield [
            [
                'uuid' => new Type(Type::BUILTIN_TYPE_OBJECT, true, Uuid::class)
            ],
            FullRestObject::class,
        ];
        yield [
            [
                'one' => new Type(Type::BUILTIN_TYPE_FLOAT, false),
                'two' => new Type(Type::BUILTIN_TYPE_FLOAT, false),
            ],
            SumExample::class,
        ];
        yield [
            [
                'value' => null,
            ],
            ValueObject::class,
        ];
    }

    public function testInstantiate()
    {
        $testItem = new ObjectAccess();
        $this->assertEquals(new SumExample(1, 2), $testItem->instantiate(new ReflectionClass(SumExample::class), [1, 2]));
    }

    public function testGetValue()
    {
        $testItem = new ObjectAccess();
        $example = new SumExample(1, 2);
        $this->assertSame(3.0, $testItem->getValue($example, 'addition'));

        $example = new ClassWithConflictingTypehints(2.0);
        $this->assertSame('true', $testItem->getValue($example, 'boolean'));

        $example = new FullRestObject();
        $example->stringValue = 'pizza';
        $this->assertSame('pizza', $testItem->getValue($example, 'stringValue'));
    }

    public function testGetValue_field_missing()
    {
        $testItem = new ObjectAccess();
        $example = new ClassWithGetterWithErrorAndNoConstructor();
        $this->expectException(NameNotFoundException::class);
        $testItem->getValue($example, 'salami');
    }

    public function testGetValue_getter_throws_error()
    {
        $testItem = new ObjectAccess();
        $example = new ClassWithGetterWithErrorAndNoConstructor();
        $this->expectException(ObjectAccessException::class);
        $testItem->getValue($example, 'pizza');
    }

    public function testGetValue_getter_throws_error_public_property()
    {
        $testItem = new ObjectAccess();
        $example = new ClassWithGetterErrorAndPublicProperty();
        $this->expectException(ObjectAccessException::class);
        $testItem->getValue($example, 'stringValue');
    }

    public function testSetValue()
    {
        $testItem = new ObjectAccess();
        $example = new ClassWithManyTypehintsAndSetter();
        $testItem->setValue($example, 'test', true);
        $this->assertEquals(true, $example->getTest());

        $example = new SimplePopo();
        $testItem->setValue($example, 'arbitraryField', true);
        $this->assertEquals(true, $example->arbitraryField);
    }

    public function testSetValue_field_missing()
    {
        $testItem = new ObjectAccess();
        $example = new ClassWithGetterWithErrorAndNoConstructor();
        $this->expectException(NameNotFoundException::class);
        $testItem->setValue($example, 'salami', 'yummy');
    }

    public function testSetValue_fails()
    {
        $testItem = new ObjectAccess();
        $example = new ClassWithGetterErrorAndPublicProperty();
        $this->expectException(ObjectWriteException::class);
        $testItem->setValue($example, 'stringValue', 'yummy');
    }

    public function testSetValue_unaccepted_value()
    {
        $testItem = new ObjectAccess();
        $example = new ClassWithManyTypehintsAndSetter();
        $this->expectException(ObjectWriteException::class);
        $testItem->setValue($example, 'test', false);
    }


    /**
     * @dataProvider getGetterTypesProvider
     */
    public function testGetGetterTypes(array $expectedGetterTypes, array $expectedSetterTypes, string $className, string $fieldName)
    {
        $testItem = new ObjectAccess();
        $this->assertEquals($expectedGetterTypes, $testItem->getGetterTypes(new ReflectionClass($className), $fieldName));
        $this->assertEquals($expectedSetterTypes, $testItem->getSetterTypes(new ReflectionClass($className), $fieldName));
    }

    public function getGetterTypesProvider()
    {
        yield [
            [
                new Type(Type::BUILTIN_TYPE_STRING, false),
                new Type(Type::BUILTIN_TYPE_INT, false),
                new Type(Type::BUILTIN_TYPE_ARRAY, false),
                new Type(Type::BUILTIN_TYPE_BOOL, false),
            ],
            [
                new Type(Type::BUILTIN_TYPE_BOOL, false),
            ],
            ClassWithConflictingTypehints::class,
            'boolean'
        ];
        //string|int|ValueObject|bool|null
        $value = [
            new Type(Type::BUILTIN_TYPE_STRING, true),
            new Type(Type::BUILTIN_TYPE_INT, true),
            new Type(Type::BUILTIN_TYPE_OBJECT, true, ValueObject::class),
            new Type(Type::BUILTIN_TYPE_BOOL, true),
        ];
        yield [
            $value,
            $value,
            ClassWithManyTypehintsAndSetter::class,
            'test',
        ];
        yield [
            [],
            [],
            ClassWithNoTypehints::class,
            'noTypehint',
        ];
    }

    public function testGetGetterTypes_unknown_field()
    {
        $testItem = new ObjectAccess();
        $this->expectException(NameNotFoundException::class);
        $testItem->getGetterTypes(new ReflectionClass(ClassWithNoTypehints::class), 'notFound');
    }

    public function testGetSetterTypes_unknown_field()
    {
        $testItem = new ObjectAccess();
        $this->expectException(NameNotFoundException::class);
        $testItem->getSetterTypes(new ReflectionClass(ClassWithNoTypehints::class), 'notFound');
    }

    /**
     * @requires PHP >= 7.4
     */
    public function test_property_typehints()
    {
        $testItem = new ObjectAccess();
        $refl = new ReflectionClass(ClassWithPhp74PropertyTypehint::class);
        $this->assertEquals(['property'], $testItem->getGetterFields($refl));
        $this->assertEquals(['property'], $testItem->getSetterFields($refl));
        $this->assertEquals([new Type(Type::BUILTIN_TYPE_INT, false)], $testItem->getGetterTypes($refl, 'property'));
        $this->assertEquals([new Type(Type::BUILTIN_TYPE_INT, false)], $testItem->getSetterTypes($refl, 'property'));

        $object = new ClassWithPhp74PropertyTypehint();
        $testItem->setValue($object, 'property', '42');
        $this->assertEquals(42, $object->property);
        $this->assertEquals(42, $testItem->getValue($object, 'property'));
    }
}
