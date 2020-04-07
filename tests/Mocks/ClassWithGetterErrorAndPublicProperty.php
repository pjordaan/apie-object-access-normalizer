<?php


namespace W2w\Test\ApieObjectAccessNormalizer\Mocks;


use TypeError;

class ClassWithGetterErrorAndPublicProperty
{
    /**
     * @var string
     */
    public $stringValue;

    public function getStringValue(): string
    {
        throw new TypeError('Just call the public property fool!');
    }

    public function hasStringValue(): string
    {
        return $this->stringValue;
    }

    public function isStringValue(): bool
    {
        return $this->stringValue;
    }
    public function setStringValue(string $stringValue)
    {
        throw new TypeError('Just call the public property fool!');
    }
}
