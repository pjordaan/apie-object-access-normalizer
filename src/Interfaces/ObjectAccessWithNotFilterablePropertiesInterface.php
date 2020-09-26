<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Interfaces;

use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\FilteredObjectAccess;

/**
 * Interface for FilteredObjectAccess to know that this Object access has fields that can not be filtered out and
 * are always returned.
 *
 * @see FilteredObjectAccess
 */
interface ObjectAccessWithNotFilterablePropertiesInterface
{
    /**
     * @return string[]
     */
    public function getNotFilterableProperties(): array;
}
