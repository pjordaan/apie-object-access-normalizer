<?php

namespace W2w\Test\ApieObjectAccessNormalizer\Normalizers;

use DateTimeImmutable;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use W2w\Lib\Apie\Plugins\Uuid\Normalizers\UuidNormalizer;
use W2w\Lib\Apie\Plugins\ValueObject\Normalizers\ValueObjectNormalizer;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ValidationException;
use W2w\Lib\ApieObjectAccessNormalizer\Normalizers\ApieObjectAccessNormalizer;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\ObjectAccessInterface;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\PolymorphicRelationObjectAccess;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithConflictingTypehints;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithMultipleTypes;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithNoTypehints;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithoutConstructorTypehint;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithoutProperties;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithPhp74PropertyTypehint;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithSerializationGroup;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithSubclass;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithTypedArrayTypehint;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithValueObject;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\FullRestObject;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\Polymorphic\BaseClass;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\Polymorphic\MutablePizza;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\Polymorphic\SalamiPizza;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\Polymorphic\ValueObjectPizza;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\RecursiveObject;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\SumExample;

class ApieObjectAccessNormalizerTest extends TestCase
{
    private function createSerializer(ObjectAccessInterface $objectAccess = null, NameConverterInterface $nameConverter = null): Serializer
    {
        $factory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        return new Serializer(
            [
                new UuidNormalizer(),
                new DateTimeNormalizer(),
                new ValueObjectNormalizer(),
                new ApieObjectAccessNormalizer($objectAccess, $nameConverter, $factory),
                new ArrayDenormalizer(),
            ],
            [new JsonEncoder()]
        );
    }

    public function testDenormalize()
    {
        $serializer = $this->createSerializer();
        try {
            /** @var FullRestObject $result */
            $result = $serializer->denormalize(
                ['uuid' => '986e12c4-3011-4ed8-aead-c62b76bb7f69', 'stringValue' => 'a value', 'valueObject' => '2011-01-01T15:03:01.012345Z'],
                FullRestObject::class
            );
            $this->assertEquals('986e12c4-3011-4ed8-aead-c62b76bb7f69', $result->getUuid()->toString());
            $this->assertEquals('a value', $result->stringValue);
            $this->assertEquals(new DateTimeImmutable('2011-01-01T15:03:01.012345Z'), $result->valueObject);
        } catch (ValidationException $validationException) {
            $this->fail('Did not expect a validation error, got: ' . json_encode($validationException->getErrors()));
        }
    }

    public function testDenormalize_no_typehints()
    {
        $serializer = $this->createSerializer();
        try {
            /** @var ClassWithNoTypehints $result */
            $result = $serializer->denormalize(
                ['noTypehint' => null],
                ClassWithNoTypehints::class
            );
            $this->assertEquals(new ClassWithNoTypehints(null), $result);
        } catch (ValidationException $validationException) {
            $this->fail('Did not expect a validation error, got: ' . json_encode($validationException->getErrors()));
        }
    }

    public function testDenormalize_conflicting_typehints()
    {
        $serializer = $this->createSerializer();
        try {
            /** @var ClassWithNoTypehints $result */
            $result = $serializer->denormalize(
                ['boolean' => 1.5],
                ClassWithConflictingTypehints::class
            );
            $this->assertEquals(new ClassWithConflictingTypehints(1.5), $result);
        } catch (ValidationException $validationException) {
            $this->fail('Did not expect a validation error, got: ' . json_encode($validationException->getErrors()));
        }
    }

    public function testDenormalize_existing_object()
    {
        $serializer = $this->createSerializer();
        try {
            $object = new ClassWithMultipleTypes(false, 'old value');
            /** @var ClassWithMultipleTypes $result */
            $result = $serializer->denormalize(
                ['boolean' => 1, 'string' => 'string'],
                ClassWithMultipleTypes::class,
                null,
                ['object_to_populate' => $object]
            );
            $this->assertSame($object, $result);
            $this->assertNotEquals($result->getUpdatedAt(), $result->getCreatedAt());
            $this->assertEquals(true, $object->isBoolean());
            $this->assertEquals('string', $object->getString());
        } catch (ValidationException $validationException) {
            $this->fail('Did not expect a validation error, got: ' . json_encode($validationException->getErrors()));
        }
    }

    public function testDenormalize_recursive_object()
    {
        $serializer = $this->createSerializer();
        try {
            /** @var RecursiveObject $result */
            $result = $serializer->denormalize(
                ['child' => ['child' => null]],
                RecursiveObject::class
            );
            $this->assertInstanceOf(RecursiveObject::class, $result);
            $this->assertInstanceOf(RecursiveObject::class, $result->getChild());
            $this->assertEquals(null, $result->getChild()->getChild());
        } catch (ValidationException $validationException) {
            $this->fail('Did not expect a validation error, got: ' . json_encode($validationException->getErrors()));
        }
    }

    /**
     * @dataProvider validationErrorProvider
     */
    public function testDenormalize_validation_error(array $expectedError, array $input, string $className)
    {
        $serializer = $this->createSerializer();
        try {
            $result = $serializer->denormalize(
                $input,
                $className
            );
            $this->fail('I expected to get a validation error, but got ' . var_export($result, true));
        } catch (ValidationException $validationException) {
            $this->assertEquals($expectedError, $validationException->getErrors());
        }
    }

    public function validationErrorProvider()
    {
        // uuid is wrong, map as validation error:
        yield [
            ['uuid' => ['Invalid UUID string: this value is not correct']],
            ['uuid' => 'this value is not correct', 'stringValue' => 'a value', 'valueObject' => 42],
            FullRestObject::class,
        ];
        // uuid is in constructor, so the valueObject validation error is not shown
        yield [
            ['uuid' => ['Invalid UUID string: this value is not correct']],
            ['uuid' => 'this value is not correct', 'valueObject' => ''],
            ClassWithValueObject::class,
        ];

        // no typehints, but still required in constructor
        yield [
            ['noTypehint' => ['noTypehint is required']],
            [],
            ClassWithNoTypehints::class,
        ];
        // input type is wrong
        yield [
            ['one' => ['must be one of "float" ("invalid" given)']],
            ['one' => 'invalid', 'two' => 2],
            SumExample::class,
        ];
        // input type is wrong
        yield [
            ['one' => ['must be one of "float" ("NULL" given)']],
            ['one' => null, 'two' => 2],
            SumExample::class,
        ];
        // input type is wrong
        yield [
            ['one' => ['must be one of "float" ("array" given)']],
            ['one' => [2], 'two' => 2],
            SumExample::class,
        ];
        // constructor has typehint, but other properties do not
        yield [
            ['input' => ['must be one of "string" ("array" given)']],
            ['input' => ['this is an array']],
            ClassWithoutConstructorTypehint::class,
        ];
        // validation error in sub class
        yield [
            ['subClass.input' => ['must be one of "string" ("array" given)']],
            ['subClass' => ['input' => ['this is an array']]],
            ClassWithSubclass::class,
        ];

        //class with conflicting typehints still look at the constructor typehint
        yield [
            ['boolean' => ['must be one of "float" ("array" given)']],
            ['boolean' => []],
            ClassWithConflictingTypehints::class,
        ];
        yield [
            ['boolean' => ['must be one of "float" ("" given)']],
            ['boolean' => ''],
            ClassWithConflictingTypehints::class,
        ];
        $handle = fopen(__FILE__, 'r');

        if ($handle !== false) {
            fclose($handle);
            yield [
                ['boolean' => ['must be one of "float" ("resource (closed)" given)']],
                ['boolean' => $handle],
                ClassWithConflictingTypehints::class,
            ];
        }

        // PHP 7.4 property typehint
        if (PHP_VERSION_ID >= 70400) {
            yield [
                ['property' => ['must be one of "int" ("array" given)']],
                ['property' => []],
                ClassWithPhp74PropertyTypehint::class,
            ];
        }
    }

    /**
     * @dataProvider normalizeProvider
     */
    public function testNormalize(array $expectedOutput, $input)
    {
        $serializer = $this->createSerializer();
        $this->assertEquals($expectedOutput, $serializer->normalize($input));
    }

    public function normalizeProvider()
    {
        yield [
            [
                'boolean' => 'true',
            ],
            new ClassWithConflictingTypehints(1.2),
        ];

        yield [
            [
                'alsoNoTypehint' => null,
                'noTypehint' => 'pizza',
            ],
            new ClassWithNoTypehints('pizza')
        ];

        yield [
            [
                'getterOnly' => '',
            ],
            new ClassWithoutProperties(),
        ];

        yield [
            [
                'subClass' => [
                    'input' => 42,
                ]
            ],
            new ClassWithSubclass(new ClassWithoutConstructorTypehint('42'))
        ];

        yield [
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'valueObject' => null,
            ],
            new ClassWithValueObject(Uuid::fromString('550e8400-e29b-41d4-a716-446655440000'))
        ];

        $object = new ClassWithMultipleTypes(true, 'Hello');
        yield [
            [
                'boolean' => true,
                'createdAt' => $object->getCreatedAt()->format(DATE_ATOM),
                'updatedAt' => $object->getUpdatedAt()->format(DATE_ATOM),
                'string' => 'Hello',
            ],
            $object,
        ];

        $object = new ClassWithTypedArrayTypehint();
        $object->list = [
            new SumExample(1, 2),
            new SumExample(15, 42)
        ];
        yield [
            [
                'list' => [
                    ['addition' => 3],
                    ['addition' => 57],
                ],
            ],
            $object,
        ];

        $object = new RecursiveObject();
        $object->setChild($childObject = new RecursiveObject());
        $childObject->setChild(new RecursiveObject());
        yield [
            [
                'child' => ['child' => ['child' => null]],
            ],
            $object,
        ];

        $object = new ClassWithSerializationGroup();
        $object->value1 = new ClassWithSerializationGroup();
        $object->value2 = [new ClassWithNoTypehints('salami')];
        $object->value3 = new ClassWithNoTypehints(42);
        yield [
            [
                'value1' => [
                    'value1' => null,
                    'value2' => [],
                    'value3' => null,
                ],
                'value2' => [
                    [
                        'noTypehint' => 'salami',
                        'alsoNoTypehint' => null,
                    ]
                ],
                'value3' => [
                    'noTypehint' => 42,
                    'alsoNoTypehint' => null,
                ]
            ],
            $object
        ];

        // PHP 7.4 property typehint
        if (PHP_VERSION_ID >= 70400) {
            $object = new ClassWithPhp74PropertyTypehint();
            $object->property = 42;
            yield [
                ['property' => 42],
                $object,
            ];
        }
    }

    public function testNormalizeWithOtherNameConverter()
    {
        $serializer = $this->createSerializer(null, new CamelCaseToSnakeCaseNameConverter());
        $expected = [
            'also_no_typehint' => null,
            'no_typehint' => 'pizza',
        ];
        $this->assertEquals($expected, $serializer->normalize(new ClassWithNoTypehints('pizza')));
    }

    public function testDenormalizeWithOtherNameConverter()
    {
        $serializer = $this->createSerializer(null, new CamelCaseToSnakeCaseNameConverter());
        $expected = new ClassWithNoTypehints('pizza');
        $input = [
            'also_no_typehint' => null,
            'no_typehint' => 'pizza',
        ];
        $this->assertEquals($expected, $serializer->denormalize($input, ClassWithNoTypehints::class));
    }

    public function testNormalizeWithOtherNameConverterAndGroups()
    {
        AnnotationRegistry::registerLoader('class_exists');
        $serializer = $this->createSerializer(null, new CamelCaseToSnakeCaseNameConverter());
        $expected = [
        ];
        $this->assertEquals($expected, $serializer->normalize(new ClassWithNoTypehints('pizza'), null, ['groups' => ['missing']]));
        $expected = [
            'value1' => [
                'value1' => null,
                'value2' => [],
            ],
            'value2' => [[]],
        ];
        $object = new ClassWithSerializationGroup();
        $object->value1 = new ClassWithSerializationGroup();
        $object->value2 = [new ClassWithNoTypehints('salami')];
        $object->value3 = new ClassWithNoTypehints(42);
        $this->assertEquals($expected, $serializer->normalize($object, null, ['groups' => ['missing']]));
    }

    public function testNormalizeWithPolymorphicRelation()
    {
        $objectAccess = new PolymorphicRelationObjectAccess(
            BaseClass::class,
            [
                'mutable' => MutablePizza::class,
                'salami' => SalamiPizza::class,
                'valued' => ValueObjectPizza::class,
            ],
            'flavour'
        );
        $serializer = $this->createSerializer(
            $objectAccess,
            new CamelCaseToSnakeCaseNameConverter()
        );

        $actual = $serializer->normalize(new MutablePizza(), 'json');
        $this->assertEquals(['flavour' => 'mutable', 'pizza' => '<empty pizza>', 'type' => 'pizza'], $actual);
        $actual = $serializer->normalize(new SalamiPizza(), 'json');
        $this->assertEquals(['flavour' => 'salami', 'pizza' => 'salami', 'type' => 'pizza', 'spiciness' => 42], $actual);
        $actual = $serializer->normalize(new ValueObjectPizza('quattro formaggi'), 'json');
        $this->assertEquals(['flavour' => 'valued', 'pizza' => 'quattro formaggi', 'type' => 'pizza'], $actual);

        $context = ['groups' => ['get', 'base']];
        $actual = $serializer->normalize(new MutablePizza(), 'json', $context);
        $this->assertEquals(['flavour' => 'mutable'], $actual);
        $actual = $serializer->normalize(new SalamiPizza(), 'json', $context);
        $this->assertEquals(['flavour' => 'salami'], $actual);
        $actual = $serializer->normalize(new ValueObjectPizza('quattro formaggi'), 'json', $context);
        $this->assertEquals(['flavour' => 'valued'], $actual);
    }

    public function testDenormalizeWithOtherNameConverterAndGroups()
    {
        try {
            AnnotationRegistry::registerLoader('class_exists');
            $serializer = $this->createSerializer(null, new CamelCaseToSnakeCaseNameConverter());
            $expected = new ClassWithNoTypehints('pizza');
            $input = [
                'also_no_typehint' => null,
                'no_typehint'      => 'pizza',
            ];
            $this->assertEquals(
                $expected, $serializer->denormalize(
                $input, ClassWithNoTypehints::class, null, ['groups' => ['missing']]
            )
            );

            $input = [
                'value1' => [
                    'value1' => null,
                    'value2' => [],
                ],
                'value2' => [$input],
                'value3' => [
                    'no_typehint' => 42,
                ],
            ];
            $expected = new ClassWithSerializationGroup();
            $expected->value1 = new ClassWithSerializationGroup();
            $expected->value2 = [new ClassWithNoTypehints('pizza')];
            $this->assertEquals(
                $expected, $serializer->denormalize(
                $input, ClassWithSerializationGroup::class, null, ['groups' => ['missing']]
            )
            );
        } catch (ValidationException $validationException) {
            $this->fail('Did not expect a validation error, got: ' . json_encode($validationException->getErrors()));
        }
    }
}
