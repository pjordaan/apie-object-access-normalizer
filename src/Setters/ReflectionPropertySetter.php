<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Setters;

use ReflectionProperty;
use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\TypeUtils;

class ReflectionPropertySetter implements SetterInterface
{
    /**
     * @var ReflectionProperty
     */
    private $property;

    public function __construct(ReflectionProperty $method)
    {
        $this->property = $method;
    }

    public function setValue($object, $newValue)
    {
        $this->property->setValue($object, $newValue);
    }

    public function getName(): string
    {
        return $this->property->getName();
    }

    public function getPriority(): int
    {
        return 3;
    }

    public function toType(): ?Type
    {
        return TypeUtils::convertPropertyToType($this->property);
    }
}
