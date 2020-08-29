<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Getters;

use ReflectionMethod;
use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\Interfaces\LocalizationAwareInterface;
use W2w\Lib\ApieObjectAccessNormalizer\TypeUtils;

final class ReflectionLocalizedGetterMethod implements GetterInterface
{
    /**
     * @var ReflectionMethod
     */
    private $method;

    /**
     * @var callable
     */
    private $conversionFn;

    /**
     * @var LocalizationAwareInterface
     */
    private $localizationAware;

    public function __construct(ReflectionMethod $method, LocalizationAwareInterface  $localizationAware, callable $conversionFn)
    {
        $this->method = $method;
        $this->localizationAware = $localizationAware;
        $this->conversionFn = $conversionFn;
    }

    public function getName(): string
    {
        return $this->method->getName();
    }

    public function getValue($object)
    {
        return $this->method->invoke($object, call_user_func($this->conversionFn, $this->localizationAware->getAcceptLanguage()));
    }

    public function getPriority(): int
    {
        return 4;
    }

    public function toType(): ?Type
    {
        return TypeUtils::convertMethodToType($this->method);
    }
}
