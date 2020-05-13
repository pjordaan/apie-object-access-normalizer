<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess;

use Symfony\Component\PropertyInfo\Type;

/**
 * A class implementing this interface can provide its own field information.
 *
 * @see SelfObjectAccessInterface
 */
interface SelfObjectAccessInterface
{
    /**
     * Returns all property names that can be retrieved.
     *
     * @return string[]
     */
    public static function getGetterFields(): array;

    /**
     * Returns all property names that can be changed.
     *
     * @return string[]
     */
    public static function getSetterFields(): array;

    /**
     * Returns all types that can be returned.
     *
     * @param string $fieldName
     * @return Type[]
     */
    public static function getGetterTypes(string $fieldName): array;

    /**
     * Returns all types that can be used for changing a value.
     *
     * @param string $fieldName
     * @return array
     */
    public static function getSetterTypes(string $fieldName): array;

    /**
     * Returns all types to instantiate an object.
     *
     * @return Type[]
     */
    public static function getConstructorArguments(): array;

    /**
     * Gets a property value.
     *
     * @param string $fieldName
     * @return mixed
     */
    public function getFieldNameValue(string $fieldName);

    /**
     * Sets a property instance value.
     *
     * @param string $fieldName
     * @param mixed $value
     * @return mixed
     */
    public function setFieldNameValue(string $fieldName, $value);

    /**
     * Instantiate the object with the arguments provided here.
     *
     * @param array $constructorArgs
     * @return object
     */
    public static function instantiate(array $constructorArgs): object;

    /**
     * Returns a string description of the property.
     *
     * @param string $fieldName
     * @param bool $preferGetters
     * @return string|null
     */
    public static function getDescription(string $fieldName, bool $preferGetters): ?string;
}
