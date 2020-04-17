<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess;

use ReflectionClass;

/**
 * Extension of ObjectAccessInterface that is being used by GroupedObjectAccess to tell a specific object access
 * can be used for a specific class.
 */
interface ObjectAccessSupportedInterface extends ObjectAccessInterface
{
    /**
     * Returns true if class is supported for this ObjectAccess instance.
     *
     * @param ReflectionClass $reflectionClass
     * @return bool
     */
    public function isSupported(ReflectionClass $reflectionClass): bool;
}
