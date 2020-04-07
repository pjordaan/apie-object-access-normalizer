<?php


namespace W2w\Test\ApieObjectAccessNormalizer\Mocks;


use RuntimeException;

class ClassWithGetterWithErrorAndNoConstructor
{
    public function getPizza(): string
    {
        throw new RuntimeException('Out of pizza');
    }
}
