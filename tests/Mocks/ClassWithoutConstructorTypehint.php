<?php


namespace W2w\Test\ApieObjectAccessNormalizer\Mocks;

/**
 * Used by ApieObjectAccessNormalizerTest
 *
 * tests missing typehint in constructor, but still with typehint on the property.
 */
class ClassWithoutConstructorTypehint
{
    /**
     * @var string
     */
    private $input;

    public function __construct($input)
    {
        $this->input = $input;
    }

    public function setInput(string $input): self
    {
        $this->input = $input;
        return $this;
    }

    public function getInput(): string
    {
        return $this->input;
    }
}
