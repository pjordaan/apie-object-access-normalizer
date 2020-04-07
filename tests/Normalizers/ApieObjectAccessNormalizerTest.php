<?php

namespace W2w\Test\ApieObjectAccessNormalizer\Normalizers;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use W2w\Lib\Apie\Plugins\Uuid\Normalizers\UuidNormalizer;
use W2w\Lib\Apie\Plugins\ValueObject\Normalizers\ValueObjectNormalizer;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ValidationException;
use W2w\Lib\ApieObjectAccessNormalizer\Normalizers\ApieObjectAccessNormalizer;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithConflictingTypehints;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithMultipleTypes;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithNoTypehints;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithoutConstructorTypehint;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithoutProperties;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithPhp74PropertyTypehint;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithSubclass;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithTypedArrayTypehint;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ClassWithValueObject;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\FullRestObject;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\RecursiveObject;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\SumExample;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\ValueObject;

class ApieObjectAccessNormalizerTest extends TestCase
{
    private function createSerializer(): Serializer
    {
        return new Serializer(
            [new UuidNormalizer(), new DateTimeNormalizer(), new ValueObjectNormalizer(), new ApieObjectAccessNormalizer()],
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
        // object with no normalization configured
        yield [
            ['valueObject' => ['Could not denormalize object of type "W2w\Test\ApieObjectAccessNormalizer\Mocks\ValueObject", no supporting normalizer found.']],
            ['uuid' => '986e12c4-3011-4ed8-aead-c62b76bb7f69', 'valueObject' => ''],
            ClassWithValueObject::class,
        ];

        // no typehints, but still required in constructor
        yield [
            ['noTypehint' => ['required']],
            [],
            ClassWithNoTypehints::class,
        ];
        // input type is wrong
        yield [
            ['one' => ['I expect float but got "invalid"']],
            ['one' => 'invalid', 'two' => 2],
            SumExample::class,
        ];
        // input type is wrong
        yield [
            ['one' => ['I expect float but got NULL']],
            ['one' => null, 'two' => 2],
            SumExample::class,
        ];
        // input type is wrong
        yield [
            ['one' => ['I expect float but got array']],
            ['one' => [2], 'two' => 2],
            SumExample::class,
        ];
        // constructor has typehint, but other properties do not
        yield [
            ['input' => ['I expect string but got array']],
            ['input' => ['this is an array']],
            ClassWithoutConstructorTypehint::class,
        ];
        // validation error in sub class
        yield [
            ['subClass.input' => ['I expect string but got array']],
            ['subClass' => ['input' => ['this is an array']]],
            ClassWithSubclass::class,
        ];

        //class with conflicting typehints still look at the constructor typehint
        yield [
            ['boolean' => ['I expect float but got array']],
            ['boolean' => []],
            ClassWithConflictingTypehints::class,
        ];
        yield [
            ['boolean' => ['I expect float but got ""']],
            ['boolean' => ''],
            ClassWithConflictingTypehints::class,
        ];
        $handle = fopen(__FILE__, 'r');

        if ($handle !== false) {
            fclose($handle);
            yield [
                ['boolean' => ['I expect float but got resource (closed)']],
                ['boolean' => $handle],
                ClassWithConflictingTypehints::class,
            ];
        }

        // PHP 7.4 property typehint
        if (PHP_VERSION_ID >= 70400) {
            yield [
                ['property' => ['I expect int but got array']],
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
}
