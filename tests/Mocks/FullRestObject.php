<?php
namespace W2w\Test\ApieObjectAccessNormalizer\Mocks;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

class FullRestObject
{
    /**
     * @var Uuid
     */
    private $uuid;

    /**
     * @var string
     */
    public $stringValue;

    /**
     * @var DateTimeImmutable
     */
    public $valueObject;

    public function __construct(?Uuid $uuid = null)
    {
        $this->uuid = $uuid ?? Uuid::uuid4();
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }
}
