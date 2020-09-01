<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess;

use ReflectionClass;
use W2w\Lib\ApieObjectAccessNormalizer\Getters\GetterInterface;
use W2w\Lib\ApieObjectAccessNormalizer\Getters\ReflectionLocalizedGetterMethod;
use W2w\Lib\ApieObjectAccessNormalizer\Interfaces\LocalizationAwareInterface;
use W2w\Lib\ApieObjectAccessNormalizer\Setters\ReflectionLocalizedSetterMethod;
use W2w\Lib\ApieObjectAccessNormalizer\Setters\SetterInterface;

class LocalizationAwareObjectAccess extends ObjectAccess
{
    /**
     * @var GetterInterface[][][]
     */
    private $getterCache = [];

    /**
     * @var SetterInterface[][][]
     */
    private $setterCache = [];

    /**
     * @var LocalizationAwareInterface
     */
    private $localizationAware;

    /**
     * @var callable
     */
    private $conversionFn;

    public function __construct(LocalizationAwareInterface  $localizationAware, callable $conversionFn, bool $publicOnly = true, bool $disabledConstructor = false)
    {
        $this->localizationAware = $localizationAware;
        $this->conversionFn = $conversionFn;
        parent::__construct($publicOnly, $disabledConstructor);
    }

    protected function getGetterMapping(ReflectionClass $reflectionClass): array
    {
        $className= $reflectionClass->getName();
        if (isset($this->getterCache[$className])) {
            return $this->getterCache[$className];
        }

        $attributes = parent::getGetterMapping($reflectionClass);

        $reflectionMethods = $this->getAvailableMethods($reflectionClass);
        foreach ($reflectionMethods as $method) {
            if ($method->getNumberOfRequiredParameters() === 1
                && !$method->isStatic()
                && preg_match('/^(get|has|is)[A-Z0-9]/i', $method->name)) {
                $fieldName = lcfirst(substr($method->name, 0 === strpos($method->name, 'is') ? 2 : 3));
                $attributes[$fieldName][] = new ReflectionLocalizedGetterMethod(
                    $method,
                    $this->localizationAware,
                    $this->conversionFn
                );
            }
        }

        foreach ($attributes as &$methods) {
            $this->sort($methods);
        }

        return $this->getterCache[$className] = $attributes;
    }

    protected function getSetterMapping(ReflectionClass $reflectionClass): array
    {
        $className= $reflectionClass->getName();
        if (isset($this->setterCache[$className])) {
            return $this->setterCache[$className];
        }

        $attributes = parent::getSetterMapping($reflectionClass);

        $reflectionMethods = $this->getAvailableMethods($reflectionClass);
        foreach ($reflectionMethods as $method) {
            if ($method->getNumberOfRequiredParameters() === 2
                && !$method->isStatic()
                && preg_match('/^(set)[A-Z0-9]/i', $method->name)) {
                $fieldName = lcfirst(substr($method->name, 3));
                $attributes[$fieldName][] = new ReflectionLocalizedSetterMethod(
                    $method,
                    $this->localizationAware,
                    $this->conversionFn
                );
            }
        }

        return $this->setterCache[$className] = $attributes;
    }
}
