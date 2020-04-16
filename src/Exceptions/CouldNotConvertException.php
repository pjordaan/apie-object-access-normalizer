<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;

use W2w\Lib\ApieObjectAccessNormalizer\Utils;

/**
 * Thrown if a value can not be converted to a built in php type.
 *
 * @see Utils
 */
class CouldNotConvertException extends ApieException
{
    public function __construct(string $wantedType, string $displayValue)
    {
        parent::__construct(429, 'must be one of "' . $wantedType . '" ("' . $displayValue . '" given)');
    }
}
