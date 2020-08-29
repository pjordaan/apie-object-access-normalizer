<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Setters;

use ReflectionMethod;
use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\TypeUtils;

class ReflectionMethodSetter implements SetterInterface
{
    /**
     * @var ReflectionMethod
     */
    private $method;

    public function __construct(ReflectionMethod $method)
    {
        $this->method = $method;
    }

    public function setValue($object, $newValue)
    {
        return $this->method->invoke($object, $newValue);
    }

    public function getName(): string
    {
        return $this->method->getName();
    }

    public function getPriority(): int
    {
        return 3;
    }

    public function toType(): ?Type
    {
        return TypeUtils::convertMethodToType($this->method);
    }
}
