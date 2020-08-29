<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\Setters;

use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\Interfaces\PriorityAwareInterface;

interface SetterInterface extends PriorityAwareInterface
{
    /**
     * Gets name of setter.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns type of getter
     *
     * @return Type|null
     */
    public function toType(): ?Type;

    /**
     * @param object|array $object
     * @param mixed $value
     */
    public function setValue($object, $value);
}
