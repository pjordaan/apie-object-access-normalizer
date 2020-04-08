<?php
namespace W2w\Lib\ApieObjectAccessNormalizer\NameConverters;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class NullNameConverter implements NameConverterInterface
{
    public function normalize($propertyName)
    {
        return $this->denormalize($propertyName);
    }

    public function denormalize($propertyName)
    {
        return $propertyName;
    }
}
