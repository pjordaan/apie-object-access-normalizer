<?php
namespace W2w\Lib\ApieObjectAccessNormalizer\Errors;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\TestCase1\ValidationException as ValidationExceptionCase1;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\TestCase2\ValidationException as ValidationExceptionCase2;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\TestCase3\ValidationException as ValidationExceptionCase3;
use W2w\Test\ApieObjectAccessNormalizer\Mocks\TestCase4\ValidationException as ValidationExceptionCase4;

class ErrorBagTest extends TestCase
{
    /**
     * @dataProvider worksProvider()
     */
    public function test_works(array $expectedErrors, string $prefix, string $fieldName, Throwable $throwable)
    {
        $testItem = new ErrorBag($prefix);
        $testItem->addThrowable($fieldName, $throwable);
        $this->assertEquals($expectedErrors, $testItem->getErrors());
    }

    public function worksProvider()
    {
        yield [
            ['field' => ['Error message']],
            '',
            'field',
            new RuntimeException('Error message'),
        ];

        yield [
            ['prefix.field' => ['Error message']],
            'prefix',
            'field',
            new RuntimeException('Error message'),
        ];

        yield [
            ['field' => ['A validation error occured']],
            '',
            'field',
            new ValidationExceptionCase1(),
        ];

        yield [
            ['prefix.field' => ['A validation error occured']],
            'prefix',
            'field',
            new ValidationExceptionCase1(),
        ];

        yield [
            [
                'field' => ['error 1'],
                'field.field 2' => ['error 2'],
            ],
            '',
            'field',
            new ValidationExceptionCase2(),
        ];

        yield [
            [
                'prefix.field.field' => ['error 1'],
                'prefix.field.field 2' => ['error 2'],
            ],
            'prefix',
            'field',
            new ValidationExceptionCase2(),
        ];

        yield [
            [
                'field.0' => ['Oh no you killed kenny!'],
            ],
            '',
            'field',
            new ValidationExceptionCase3(),
        ];

        yield [
            [
                'prefix.field.0' => ['Oh no you killed kenny!'],
            ],
            'prefix',
            'field',
            new ValidationExceptionCase3(),
        ];

        yield [
            [
                'field' => ['error 1', 'error 2'],
                'field.field 2' => ['error 3'],
            ],
            '',
            'field',
            new ValidationExceptionCase4(),
        ];

        yield [
            [
                'prefix.field.field' => ['error 1', 'error 2'],
                'prefix.field.field 2' => ['error 3'],
            ],
            'prefix',
            'field',
            new ValidationExceptionCase4(),
        ];
    }
}
