<?php
namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;

/**
 * Error thrown if a property name could not be found or has no methods or properties.
 */
class NameNotFoundException extends ApieException implements LocalizationableException
{
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
        parent::__construct(500, 'Name "' . $name . '" not found!"');
    }

    public function getI18n(): LocalizationInfo
    {
        return new LocalizationInfo(
            'general.name_not_found',
            ['name' => $this->name]
        );
    }
}
