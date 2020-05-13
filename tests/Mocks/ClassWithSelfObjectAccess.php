<?php


namespace W2w\Test\ApieObjectAccessNormalizer\Mocks;


use InvalidArgumentException;
use Symfony\Component\PropertyInfo\Type;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\SelfObjectAccessInterface;

class ClassWithSelfObjectAccess implements SelfObjectAccessInterface
{
    const VALID_KEYS = ['one', 'two', 'three'];

    private $input = [
        'one' => 1,
        'two' => 2,
        'three' => 3,
    ];

    public function __construct(array $input)
    {
        foreach ($input as $key => $value) {
            $this->$key = $value;
        }
    }

    public function __set($key, int $value)
    {
        if (!in_array($key, self::VALID_KEYS)) {
            throw new InvalidArgumentException('$key should be one, two, or three');
        }
        $this->input[$key] = $value;
    }

    public function __get($key): int
    {
        if (!in_array($key, self::VALID_KEYS)) {
            throw new InvalidArgumentException('$key should be one, two, or three');
        }
        return $this->input[$key];
    }

    /**
     * {@inheritDoc}
     */
    public static function getGetterFields(): array
    {
        return self::VALID_KEYS;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSetterFields(): array
    {
        return self::VALID_KEYS;
    }

    /**
     * {@inheritDoc}
     */
    public static function getGetterTypes(string $fieldName): array
    {
        if (!in_array($fieldName, self::VALID_KEYS)) {
            throw new InvalidArgumentException('$key should be one, two, or three');
        }
        return [new Type(Type::BUILTIN_TYPE_INT, false)];
    }

    /**
     * {@inheritDoc}
     */
    public static function getSetterTypes(string $fieldName): array
    {
        if (!in_array($fieldName, self::VALID_KEYS)) {
            throw new InvalidArgumentException('$key should be one, two, or three');
        }
        return [new Type(Type::BUILTIN_TYPE_INT, false)];
    }

    /**
     * {@inheritDoc}
     */
    public static function getConstructorArguments(): array
    {
        return [
            'input' => new Type(
                Type::BUILTIN_TYPE_ARRAY,
                false,
                null,
                true,
                new Type(Type::BUILTIN_TYPE_STRING, false),
                new Type(Type::BUILTIN_TYPE_INT, false)
            )
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldNameValue(string $fieldName)
    {
        return $this->$fieldName;
    }

    /**
     * {@inheritDoc}
     */
    public function setFieldNameValue(string $fieldName, $value)
    {
        $this->$fieldName = $value;
    }

    /**
     * {@inheritDoc}
     */
    public static function instantiate(array $constructorArgs): object
    {
        return new self($constructorArgs['input'] ?? []);
    }

    /**
     * {@inheritDoc}
     */
    public static function getDescription(string $fieldName, bool $preferGetters): ?string
    {
        if (!in_array($fieldName, self::VALID_KEYS)) {
            throw new InvalidArgumentException('$key should be one, two, or three');
        }
        return $fieldName;
    }
}
