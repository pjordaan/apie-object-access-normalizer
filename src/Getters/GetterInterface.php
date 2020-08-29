<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\Getters;

use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\Interfaces\PriorityAwareInterface;

interface GetterInterface extends PriorityAwareInterface
{
    /**
     * Gets name of getter.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * @param object|array $object
     * @return mixed
     */
    public function getValue($object);

    /**
     * Returns type of getter
     *
     * @return Type|null
     */
    public function toType(): ?Type;
}
