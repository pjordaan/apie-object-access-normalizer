<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;

use ReflectionMethod;
use ReflectionProperty;
use Throwable;

class ObjectAccessException extends ApieException
{
    /**
     * @param ReflectionMethod|ReflectionProperty $method
     * @param string $fieldName
     * @param Throwable $previous
     */
    public function __construct(
        $method,
        string $fieldName,
        Throwable $previous
    ) {
        $message = 'Could not access property "' . $fieldName . '" from ' . $method->getName() . ': ' . $previous->getMessage();
        parent::__construct(500, $message, $previous);
    }
}
