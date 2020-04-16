<?php
namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;

/**
 * Error thrown if a property name could not be found or has no methods or properties.
 */
class NameNotFoundException extends ApieException
{
    public function __construct(string $name)
    {
        parent::__construct(500, 'Name "' . $name . '" not found!"');
    }
}
