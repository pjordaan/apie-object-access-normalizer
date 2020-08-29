<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\Getters;

use ReflectionProperty;
use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\TypeUtils;

class ReflectionPropertyGetter implements GetterInterface
{
    /**
     * @var ReflectionProperty
     */
    private $property;

    public function __construct(ReflectionProperty $property)
    {
        $this->property = $property;
    }

    public function getName(): string
    {
        return $this->property->getName();
    }

    public function getValue($object)
    {
        return $this->property->getValue($object);
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function toType(): ?Type
    {
        return TypeUtils::convertPropertyToType($this->property);
    }
}
