<?php

namespace W2w\Test\ApieObjectAccessNormalizer\Mocks;

class RecursiveObject
{
    /**
     * @var RecursiveObject
     */
    private $child;

    public function setChild(?RecursiveObject $child): self
    {
        $this->child = $child;
        return $this;
    }

    public function getChild(): ?RecursiveObject
    {
        return $this->child;
    }
}
