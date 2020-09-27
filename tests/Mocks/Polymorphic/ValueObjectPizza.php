<?php

namespace W2w\Test\ApieObjectAccessNormalizer\Mocks\Polymorphic;

class ValueObjectPizza extends BaseClass
{
    private $pizza;

    public function __construct(string $pizza)
    {
        $this->pizza = $pizza;
    }

    public function getPizza(): string
    {
        return $this->pizza;
    }
}
