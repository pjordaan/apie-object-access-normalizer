<?php


namespace W2w\Test\ApieObjectAccessNormalizer\Mocks;

use RuntimeException;
use W2w\Lib\ApieObjectAccessNormalizer\Utils;

class ValueObject
{
    public function __construct($value) {
        throw new RuntimeException(Utils::toString($value));
    }
}
