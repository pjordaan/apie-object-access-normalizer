<?php

namespace W2w\Test\ApieObjectAccessNormalizer\Mocks\Polymorphic;

class MutablePizza extends BaseClass
{
    private $pizza = '<empty pizza>';

    public function setPizza(string $pizza)
    {
        $this->pizza = $pizza;
    }

    public function getPizza(): string
    {
        return $this->pizza;
    }
}
