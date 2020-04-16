<?php
namespace W2w\Lib\ApieObjectAccessNormalizer\NameConverters;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Fallback name converter used by ApieObjectAccessNormalizer if none were injected in the constructor.
 * Does nothing.
 */
class NullNameConverter implements NameConverterInterface
{
    /**
     * {@inheritDoc}
     */
    public function normalize($propertyName)
    {
        return $this->denormalize($propertyName);
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize($propertyName)
    {
        return $propertyName;
    }
}
