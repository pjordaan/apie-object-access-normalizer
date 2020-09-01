<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\Getters;

use ReflectionMethod;
use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\TypeUtils;

/**
 * Wrapper around a getter method.
 */
class ReflectionMethodGetter implements GetterInterface
{
    /**
     * @var ReflectionMethod
     */
    private $method;

    public function __construct(ReflectionMethod $method)
    {
        $this->method = $method;
    }

    public function getName(): string
    {
        return $this->method->getName();
    }

    public function getValue($object)
    {
        return $this->method->invoke($object);
    }

    public function getPriority(): int
    {
        $methodName = $this->method->getName();
        // getXXx method
        if (strpos($methodName, 'get') === 0) {
            return 3;
        }
        // isXxx method
        if (strpos($methodName, 'is') === 0) {
            return 2;
        }
        // hasXxx Method
        return 1;
    }

    public function toType(): ?Type
    {
        return TypeUtils::convertMethodToType($this->method);
    }
}
