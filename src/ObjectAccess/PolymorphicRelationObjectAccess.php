<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess;

use ReflectionClass;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use W2w\Lib\Apie\Exceptions\BadConfigurationException;
use W2w\Lib\ApieObjectAccessNormalizer\Errors\ErrorBag;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\CouldNotConvertException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ValidationException;
use W2w\Lib\ApieObjectAccessNormalizer\Interfaces\ObjectAccessWithNotFilterablePropertiesInterface;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\Getters\DiscriminatorColumn;

class PolymorphicRelationObjectAccess extends ObjectAccess implements
    ObjectAccessSupportedInterface,
    ObjectAccessWithNotFilterablePropertiesInterface {
    /**
     * @var string
     */
    private $baseClass;
    /**
     * @var array
     */
    private $subClasses;
    /**
     * @var string
     */
    private $discriminatorColumn;

    public function __construct(
        string $baseClass,
        array $subClasses,
        string $discriminatorColumn = 'type',
        bool $publicOnly = true,
        bool $disabledConstructor = false
    ) {
        if (array_search($baseClass, $subClasses) !== false) {
            throw new BadConfigurationException('You can not map the base class as a subclass!');
        }
        $this->baseClass = $baseClass;
        $this->subClasses = $subClasses;
        $this->discriminatorColumn = $discriminatorColumn;
        parent::__construct($publicOnly, $disabledConstructor);
    }

    private function findClass(string $search): ?ReflectionClass
    {
        foreach ($this->subClasses as $className => $discriminator) {
            if ($discriminator === $search) {
                return new ReflectionClass($search);
            }
        }
        return null;
    }

    public function getConstructorArguments(ReflectionClass $reflectionClass, array $data = []): array
    {
        $discriminator = $data[$this->discriminatorColumn] ?? null;
        if (isset($discriminator)) {
            $reflectionClass = $this->findClass($discriminator) ?? $reflectionClass;
        }
        $arguments = parent::getConstructorArguments($reflectionClass);
        if (isset($arguments[$this->discriminatorColumn])) {
            throw new BadConfigurationException("You can not map the discriminator column in the constructor!");
        }
        return [$this->discriminatorColumn => new Type(Type::BUILTIN_TYPE_STRING)] + $arguments;
    }

    protected function getGetterMapping(ReflectionClass $reflectionClass): array
    {
        $mapping = parent::getGetterMapping($reflectionClass);
        $mapping[$this->discriminatorColumn] = [new DiscriminatorColumn($this->discriminatorColumn, $this->subClasses)];
        return $mapping;
    }

    public function instantiate(ReflectionClass $reflectionClass, array $constructorArgs): object
    {
        if (!isset($constructorArgs[$this->discriminatorColumn])) {
            $errorBag = new ErrorBag('');
            $errorBag->addThrowable(
                $this->discriminatorColumn,
                new MissingConstructorArgumentsException($this->discriminatorColumn . ' is required')
            );
            throw new ValidationException($errorBag);
        }
        $discriminator = $constructorArgs[$this->discriminatorColumn];
        if (!isset($this->subClasses[$discriminator])) {
            $errorBag = new ErrorBag('');
            $errorBag->addThrowable(
                $this->discriminatorColumn,
                new CouldNotConvertException(
                    json_encode(array_keys($this->subClasses)),
                    json_encode($discriminator)
                )
            );
            throw new ValidationException($errorBag);
        }
        $discriminatorClass = $this->subClasses[$discriminator];
        if (!is_a($discriminatorClass, $reflectionClass->name, true)) {
            $errorBag = new ErrorBag('');
            $errorBag->addThrowable(
                $this->discriminatorColumn,
                new CouldNotConvertException(
                    'ReflectionClass<' . $reflectionClass->name . '>',
                    'ReflectionClass<' . $discriminatorClass . '|subtype of ' . $discriminatorClass . '>'
                )
            );
            throw new ValidationException($errorBag);
        }
        unset($constructorArgs[$this->discriminatorColumn]);
        return parent::instantiate(new ReflectionClass($discriminatorClass), $constructorArgs);
    }

    public function isSupported(ReflectionClass $reflectionClass): bool
    {
        if ($reflectionClass->name === $this->baseClass) {
            return true;
        }
        foreach ($this->subClasses as $subClass) {
            if ($reflectionClass->name === $subClass) {
                return true;
            }
        }
        foreach ($this->subClasses as $subClass) {
            if (is_a($reflectionClass->name, $subClass, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * THe discriminator column can not be filtered out.
     *
     * @return string[]
     */
    public function getNotFilterableProperties(): array
    {
        return [$this->discriminatorColumn];
    }
}
