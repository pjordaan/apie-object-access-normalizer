<?php


namespace W2w\Test\ApieObjectAccessNormalizer\Mocks;


class ClassWithoutProperties
{
    public function setSetterOnly(string $setterOnly): self
    {
        return $this;
    }

    public function getGetterOnly(): string
    {
        return '';
    }
}
