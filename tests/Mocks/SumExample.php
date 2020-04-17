<?php
namespace W2w\Test\ApieObjectAccessNormalizer\Mocks;

class SumExample
{
    /**
     * First number
     * @var float
     */
    private $one;

    /**
     * @var float
     */
    private $two;

    public function __construct(float $one, float $two)
    {
        $this->one = $one;
        $this->two = $two;
    }

    public function getAddition(): float
    {
        return $this->one + $this->two;
    }
}
