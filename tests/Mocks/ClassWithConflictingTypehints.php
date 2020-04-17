<?php

namespace W2w\Test\ApieObjectAccessNormalizer\Mocks;

class ClassWithConflictingTypehints
{
    /**
     * @var bool
     */
    public $boolean;

    public function __construct(float $boolean)
    {
        $this->boolean = $boolean > 1.0;
    }

    public function isBoolean(): int
    {
        return (int) $this->boolean;
    }

    public function hasBoolean(): array
    {
        return [$this->boolean];
    }

    public function getBoolean(): string
    {
        return json_encode($this->boolean);
    }

    public function setBoolean($boolean): void
    {
        $this->boolean = $boolean;
    }
}
