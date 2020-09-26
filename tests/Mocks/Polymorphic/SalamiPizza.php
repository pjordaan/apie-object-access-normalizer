<?php


namespace W2w\Test\ApieObjectAccessNormalizer\Mocks\Polymorphic;

class SalamiPizza extends BaseClass
{
    private $spiciness = 42;

    public function getPizza(): string
    {
        return 'salami';
    }

    public function getSpiciness(): int
    {
        return $this->spiciness;
    }

    public function setSpiciness(int $spiciness)
    {
        $this->spiciness = $spiciness;
    }
}
