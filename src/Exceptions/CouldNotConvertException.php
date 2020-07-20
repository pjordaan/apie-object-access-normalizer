<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;

use W2w\Lib\ApieObjectAccessNormalizer\Utils;

/**
 * Thrown if a value can not be converted to a built in php type.
 *
 * @see Utils
 */
class CouldNotConvertException extends ApieException implements LocalizationableException
{
    /**
     * @var string
     */
    private $wantedType;

    /**
     * @var string
     */
    private $displayValue;

    public function __construct(string $wantedType, string $displayValue)
    {
        $this->wantedType = $wantedType;
        $this->displayValue = $displayValue;
        parent::__construct(429, 'must be one of "' . $wantedType . '" ("' . $displayValue . '" given)');
    }

    public function getI18n(): LocalizationInfo
    {
        return new LocalizationInfo(
            'serialize.conversion_error',
            ['wanted' => $this->wantedType, 'given' => $this->displayValue]
        );
    }
}
