<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;

use ReflectionMethod;
use ReflectionProperty;
use Throwable;

/**
 * Exception thrown when a value could not be set.
 */
class ObjectWriteException extends ApieException
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
        $message = 'Could not write property "' . $fieldName . '" with ' . $method->getName() . ': ' . $previous->getMessage();
        parent::__construct(500, $message, $previous);
    }
}
