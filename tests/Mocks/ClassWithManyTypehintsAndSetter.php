<?php

namespace W2w\Test\ApieObjectAccessNormalizer\Mocks;

use RuntimeException;

class ClassWithManyTypehintsAndSetter
{
    /**
     * @var string|int|ValueObject|bool|null
     */
    private $test;

    public function setTest($input)
    {
        if ($input !== true) {
            throw new RuntimeException('$input should be true!, value is: ' . json_encode($input));
        }
        $this->test = $input;
    }

    public function getTest()
    {
        return $this->test;
    }
}
