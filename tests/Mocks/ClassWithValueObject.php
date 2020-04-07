<?php


namespace W2w\Test\ApieObjectAccessNormalizer\Mocks;

use Ramsey\Uuid\Uuid;

/**
 * Used for ApieObjectAccessNormalizerTest. Tests value object validation.
 */
class ClassWithValueObject
{
    /**
     * @var ValueObject|null
     */
    private $valueObject;
    /**
     * @var Uuid
     */
    private $uuid;

    public function __construct(Uuid $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getValueObject(): ?ValueObject
    {
        return $this->valueObject;
    }

    public function setValueObject(ValueObject $valueObject): ClassWithValueObject
    {
        $this->valueObject = $valueObject;
        return $this;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }
}
