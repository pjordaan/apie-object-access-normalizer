<?php


namespace W2w\Test\ApieObjectAccessNormalizer\Exceptions;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;
use W2w\Lib\ApieObjectAccessNormalizer\Errors\ErrorBag;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\CouldNotConvertException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\NameNotFoundException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ObjectAccessException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ObjectWriteException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ValidationException;

class LocalizationableExceptionTest extends TestCase
{
    public function testCouldNotConvert()
    {
        $testItem = new CouldNotConvertException(__CLASS__, 'int');
        $actual = $testItem->getI18n();
        $this->assertSame('serialize.conversion_error', $actual->getMessageString());
        $this->assertEquals(['wanted' => __CLASS__, 'given' => 'int'], $actual->getReplacements());
    }

    public function testNameNotFound()
    {
        $testItem = new NameNotFoundException('Wally');
        $actual = $testItem->getI18n();
        $this->assertSame('general.name_not_found', $actual->getMessageString());
        $this->assertEquals(['name' => 'Wally'], $actual->getReplacements());
    }

    public function testObjectAccessError()
    {
        $testItem = new ObjectAccessException(
            new ReflectionMethod(__METHOD__),
            'access',
            new RuntimeException('Internal error')
        );
        $actual = $testItem->getI18n();
        $this->assertSame('serialize.read', $actual->getMessageString());
        $this->assertEquals(['name' => __FUNCTION__, 'previous' => 'Internal error'], $actual->getReplacements());
    }

    public function testObjectWriteError()
    {
        $testItem = new ObjectWriteException(
            new ReflectionMethod(__METHOD__),
            'access',
            new RuntimeException('Internal error')
        );
        $actual = $testItem->getI18n();
        $this->assertSame('serialize.write', $actual->getMessageString());
        $this->assertEquals(['name' => __FUNCTION__, 'previous' => 'Internal error'], $actual->getReplacements());
    }

    public function testValidationError()
    {
        $testItem = new ValidationException(['test' => ['example']]);
        $actual = $testItem->getI18n();
        $this->assertSame('general.validation', $actual->getMessageString());
        $this->assertEquals(['errors' => ['test' => ['example']]], $actual->getReplacements());
    }
}
