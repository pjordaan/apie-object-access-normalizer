<?php

namespace W2w\Lib\ApieObjectAccessNormalizer;

use ReflectionClass;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\CouldNotConvertException;

class Utils
{
    public static function toInt($input): int
    {
        $displayValue = gettype($input);
        switch (gettype($input)) {
            case 'integer':
                return $input;
            case 'boolean':
                return $input ? 1 : 0;
            case 'double':
                if (round($input) === $input) {
                    return (int) $input;
                }
                $displayValue = $input;
                break;
            case 'object':
                if (!is_callable([$input, '__toString'])) {
                    $displayValue = 'object ' . (new ReflectionClass($input))->getShortName();
                    break;
                }
                $input = (string) $input;
            case 'string':
                if (!preg_match('/^\s*[1-9][0-9]*(\.0+){0,1}\s*$/', $input)) {
                    $displayValue = $input;
                    break;
                }
                return (int) $input;
        }
        throw new CouldNotConvertException('int', $displayValue);
    }

    public static function toFloat($input): float
    {
        $displayValue = gettype($input);
        switch (gettype($input)) {
            case 'integer':
                return (float) $input;
            case 'boolean':
                return $input ? 1.0 : 0.0;
            case 'double':
                return $input;
            case 'object':
                if (!is_callable([$input, '__toString'])) {
                    $displayValue = 'object ' . (new ReflectionClass($input))->getShortName();
                    break;
                }
                $input = (string) $input;
            case 'string':
                if (!preg_match('/^\s*[0-9]/', $input)) {
                    $displayValue = $input;
                    break;
                }
                return (float) $input;
        }
        throw new CouldNotConvertException('float', $displayValue);
    }

    public static function toString($input): string
    {
        $displayValue = gettype($input);
        switch (gettype($input)) {
            case 'object':
            case 'integer':
            case 'boolean':
            case 'double':
            case 'string':
                return (string) $input;
        }
        throw new CouldNotConvertException('string', $displayValue);
    }

    public static function toBool($input): bool
    {
        $displayValue = gettype($input);
        switch (gettype($input)) {
            case 'object':
            case 'integer':
            case 'boolean':
            case 'double':
            case 'string':
                return (bool) $input;
        }
        throw new CouldNotConvertException('bool', $displayValue);
    }
}
