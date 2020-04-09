<?php
namespace W2w\Test\ApieObjectAccessNormalizer;

use PHPUnit\Framework\TestCase;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\CouldNotConvertException;
use W2w\Lib\ApieObjectAccessNormalizer\Utils;

class UtilsTest extends TestCase
{
    /**
     * @dataProvider toIntProvider
     */
    public function testToInt(int $expected, $input)
    {
        $this->assertEquals($expected, Utils::toInt($input));
    }

    public function toIntProvider()
    {
        yield [1, 1];
        yield [1, 1.0];
        yield [1, '1'];
        yield [1, '1.0'];
        yield [1, true];
        yield [2, new class() {
            public function __toString()
            {
                return '2';
            }
        }];
    }

    /**
     * @dataProvider toFloatProvider
     */
    public function testToFloat(float $expected, $input)
    {
        $this->assertEquals($expected, Utils::toFloat($input));
    }

    public function toFloatProvider()
    {
        yield [1.0, 1];
        yield [1.5, 1.5];
        yield [1.0, '1'];
        yield [1.5, '1.5'];
        yield [1.0, true];
        yield [2.5, new class() {
            public function __toString()
            {
                return '2.5';
            }
        }];
    }

    /**
     * @dataProvider toStringProvider
     */
    public function testToString(string $expected, $input)
    {
        $this->assertEquals($expected, Utils::toString($input));
    }

    public function toStringProvider()
    {
        yield ['1', 1];
        yield ['1.5', 1.5];
        yield ['1', '1'];
        yield ['1.5', '1.5'];
        yield ["1", true];
        yield ["", false];
        yield ['2.5', new class() {
            public function __toString()
            {
                return '2.5';
            }
        }];
    }

    /**
     * @dataProvider toBoolProvider
     */
    public function testToBool(bool $expected, $input)
    {
        $this->assertEquals($expected, Utils::toBool($input));
    }

    public function toBoolProvider()
    {
        yield [true, 1];
        yield [true, 1.5];
        yield [false, 0];
        yield [true, 1.5];
        yield [true, true];
        yield [false, false];
        yield [true, new class() {
            public function __toString()
            {
                return '';
            }
        }];
    }

    /**
     * @dataProvider toIntInvalidProvider
     */
    public function testToInt_invalid(string $expectedExceptionMessage, $input)
    {
        $this->expectException(CouldNotConvertException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        Utils::toInt($input);
    }

    public function toIntInvalidProvider()
    {
        yield ['must be one of "int" ("1.5" given)', 1.5];
        yield ['must be one of "int" ("1.5" given)', '1.5'];
        $resource = fopen(__FILE__, 'r');
        if ($resource !== false) {
            fclose($resource);
            yield ['must be one of "int" ("resource (closed)" given)', $resource];
        }
        yield ['must be one of "int" ("object UtilsTest" given)', $this];
        yield ['must be one of "int" ("text" given)', 'text'];
        yield ['must be one of "int" ("text" given)', new class() {
            public function __toString()
            {
                return 'text';
            }
        }];
    }

    /**
     * @dataProvider toFloatInvalidProvider
     */
    public function testToFloat_invalid(string $expectedExceptionMessage, $input)
    {
        $this->expectException(CouldNotConvertException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        Utils::toFloat($input);
    }

    public function toFloatInvalidProvider()
    {
        $resource = fopen(__FILE__, 'r');
        if ($resource !== false) {
            fclose($resource);
            yield ['must be one of "float" ("resource (closed)" given)', $resource];
        }
        yield ['must be one of "float" ("object UtilsTest" given)', $this];
        yield ['must be one of "float" ("text" given)', 'text'];
        yield ['must be one of "float" ("text" given)', new class() {
            public function __toString()
            {
                return 'text';
            }
        }];
    }

    /**
     * @dataProvider toStringInvalidProvider
     */
    public function testToString_invalid(string $expectedExceptionMessage, $input)
    {
        $this->expectException(CouldNotConvertException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        Utils::toString($input);
    }

    public function toStringInvalidProvider()
    {
        $resource = fopen(__FILE__, 'r');
        if ($resource !== false) {
            fclose($resource);
            yield ['must be one of "string" ("resource (closed)" given)', $resource];
        }
        yield ['must be one of "string" ("array" given)', []];
    }

    /**
     * @dataProvider toBoolInvalidProvider
     */
    public function testToBool_invalid(string $expectedExceptionMessage, $input)
    {
        $this->expectException(CouldNotConvertException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        Utils::toBool($input);
    }

    public function toBoolInvalidProvider()
    {
        $resource = fopen(__FILE__, 'r');
        if ($resource !== false) {
            fclose($resource);
            yield ['must be one of "bool" ("resource (closed)" given)', $resource];
        }
        yield ['must be one of "bool" ("array" given)', []];
    }
}
