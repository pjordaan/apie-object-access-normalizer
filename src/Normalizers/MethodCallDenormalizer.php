<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\Normalizers;

use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use W2w\Lib\ApieObjectAccessNormalizer\NameConverters\NullNameConverter;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\ObjectAccessInterface;

/**
 * Special denormalizer to call a reflection method and return the value back.
 *
 * Usage:
 * - type: ReflectionMethod::ClassWithNameSpace::method
 * - context['object-instance'] object to run method on
 * - context['initial-arguments'] already hydrated arguments with key = variable name.
 * - context['object-access'] used object access instance.
 */
class MethodCallDenormalizer implements ContextAwareDenormalizerInterface
{
    /**
     * @var ObjectAccessInterface
     */
    private $objectAccess;

    /**
     * @var ApieObjectAccessNormalizer
     */
    private $normalizer;

    /**
     * @var NameConverterInterface
     */
    private $nameConverter;

    public function __construct(
        ObjectAccessInterface $objectAccess,
        ApieObjectAccessNormalizer $normalizer,
        NameConverterInterface $nameConverter = null
    ) {
        $this->objectAccess = $objectAccess;
        $this->normalizer = $normalizer;
        $this->nameConverter = $nameConverter ?? new NullNameConverter();
    }

    /**
     * {@inheritDoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        if (strpos($type, 'ReflectionMethod::') !== 0) {
            return false;
        }
        return isset($context['object-instance']);
    }

    /**
     * {@inheritDoc}
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        if (!isset($context['key_prefix'])) {
            $context['key_prefix'] = '';
        }
        $method = new ReflectionMethod(substr($type, strlen('ReflectionMethod::')));
        $objectAccess = $context['object_access'] ?? $this->objectAccess;
        $arguments = $objectAccess->getMethodArguments($method, new ReflectionClass($context['object-instance']));
        $initialArguments = $context['initial-arguments'] ?? [];
        $returnObject = $initialArguments;
        foreach ($arguments as $denormalizedFieldName => $type) {
            $fieldName = $this->nameConverter->normalize($denormalizedFieldName, $type, $format, $context);
            if (isset($initialArguments[$fieldName])) {
                continue;
            }
            $returnObject[$fieldName] = $this->normalizer->denormalizeType($data, $denormalizedFieldName, $fieldName, $type, $format, $context);
        }
        return $method->invokeArgs($context['object-instance'], $returnObject);
    }
}
