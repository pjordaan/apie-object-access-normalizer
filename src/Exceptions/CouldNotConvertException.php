<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;

class CouldNotConvertException extends ApieException
{
    public function __construct(string $wantedType, string $displayValue)
    {
        parent::__construct(429, 'I expect ' . $wantedType . ' but got ' . $displayValue);
    }
}
