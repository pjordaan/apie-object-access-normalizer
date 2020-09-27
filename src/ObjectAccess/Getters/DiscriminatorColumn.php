<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\Getters;

use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\Getters\GetterInterface;

class DiscriminatorColumn implements GetterInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $discriminatorMapping;

    public function __construct(string $name, array $discriminatorMapping)
    {
        $this->name = $name;
        $this->discriminatorMapping = $discriminatorMapping;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue($object)
    {
        $objectClassName = get_class($object);
        foreach ($this->discriminatorMapping as $value => $className) {
            if ($objectClassName === $className) {
                return $value;
            }
        }
        foreach ($this->discriminatorMapping as $value => $className) {
            if (is_a($objectClassName, $className)) {
                return $value;
            }
        }
        return null;
    }

    public function toType(): ?Type
    {
        return new Type(Type::BUILTIN_TYPE_STRING);
    }

    public function getPriority(): int
    {
        return 15;
    }
}
