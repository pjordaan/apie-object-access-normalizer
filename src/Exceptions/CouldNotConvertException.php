<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;

class CouldNotConvertException extends ApieException
{
    public function __construct(string $wantedType, string $displayValue)
    {
        parent::__construct(429, 'must be one of "' . $wantedType . '" ("' . $displayValue . '" given)');
    }
}
