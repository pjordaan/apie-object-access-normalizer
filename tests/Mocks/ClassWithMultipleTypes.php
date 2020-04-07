<?php


namespace W2w\Test\ApieObjectAccessNormalizer\Mocks;

use DateTimeImmutable;

/**
 * Test class for ApieObjectAccessNormalizerTest to test all basic types
 */
class ClassWithMultipleTypes
{
    /**
     * @var bool
     */
    private $boolean;

    /**
     * @var string
     */
    private $string;

    /**
     * @var DateTimeImmutable
     */
    private $createdAt;

    /**
     * @var DateTimeImmutable
     */
    private $updatedAt;

    public function __construct(bool $boolean, string $string)
    {
        $this->boolean = $boolean;
        $this->string = $string;
        $this->updatedAt = $this->createdAt = new DateTimeImmutable();
    }

    public function setBoolean(bool $boolean): ClassWithMultipleTypes
    {
        $this->updatedAt = new DateTimeImmutable();
        $this->boolean = $boolean;
        return $this;
    }

    public function isBoolean(): bool
    {
        return $this->boolean;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setString(string $string): ClassWithMultipleTypes
    {
        $this->updatedAt = new DateTimeImmutable();
        $this->string = $string;
        return $this;
    }

    public function getString(): string
    {
        return $this->string;
    }
}
