<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;

use ReflectionMethod;
use ReflectionProperty;
use Throwable;
use W2w\Lib\ApieObjectAccessNormalizer\Getters\GetterInterface;

/**
 * Exception thrown when trying to get a property value.
 */
class ObjectAccessException extends ApieException implements LocalizationableException
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param GetterInterface $method
     * @param string $fieldName
     * @param Throwable $previous
     */
    public function __construct(
        GetterInterface $method,
        string $fieldName,
        Throwable $previous
    ) {
        $this->name = $method->getName();
        $message = 'Could not access property "' . $fieldName . '" from ' . $this->name . ': ' . $previous->getMessage();
        parent::__construct(500, $message, $previous);
    }

    public function getI18n(): LocalizationInfo
    {
        return new LocalizationInfo(
            'serialize.read',
            [
                'name' => $this->name,
                'previous' => $this->getPrevious()->getMessage(),
            ]
        );
    }
}
