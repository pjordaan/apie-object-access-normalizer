<?php

namespace W2w\Test\ApieObjectAccessNormalizer\Mocks\Polymorphic;

abstract class BaseClass
{
    final public function getType(): string
    {
        return 'pizza';
    }

    abstract public function getPizza(): string;
}
