<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Normalizers;

use ReflectionClass;
use stdClass;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Throwable;
use W2w\Lib\ApieObjectAccessNormalizer\Errors\ErrorBag;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\CouldNotConvertException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ValidationException;
use W2w\Lib\ApieObjectAccessNormalizer\NameConverters\NullNameConverter;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\FilteredObjectAccess;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\ObjectAccess;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\ObjectAccessInterface;
use W2w\Lib\ApieObjectAccessNormalizer\Utils;

class ApieObjectAccessNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    /**
     * @var ObjectAccessInterface
     */
    private $objectAccess;

    /**
     * @var NameConverterInterface|AdvancedNameConverterInterface
     */
    private $nameConverter;

    /**
     * @var ClassMetadataFactoryInterface|null
     */
    private $classMetadataFactory;

    public function __construct(
        ObjectAccessInterface $objectAccess = null,
        NameConverterInterface $nameConverter = null,
        ClassMetadataFactoryInterface $classMetadataFactory = null
    ) {
        $this->objectAccess = $objectAccess ?? new ObjectAccess();
        $this->nameConverter = $nameConverter ?? new NullNameConverter();
        $this->classMetadataFactory = $classMetadataFactory;
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        if ($data instanceof stdClass) {
            $data = json_decode(json_encode($data), true);
        }
        $context = $this->sanitizeContext($context);
        if (empty($context['object_to_populate'])) {
            $object = $this->instantiate($data, $type, $context['object_access'], $format, $context);
        } else {
            $object = $context['object_to_populate'];
        }
        /** @var ObjectAccessInterface $objectAccess */
        $objectAccess = $context['object_access'];
        if ($this->classMetadataFactory && isset($context['groups'])) {
            $objectAccess = $this->filterObjectAccess($objectAccess, $type, $context['groups']);
        }
        $reflClass = new ReflectionClass($object);
        $setterFields = $objectAccess->getSetterFields($reflClass);
        $errors = new ErrorBag($context['key_prefix']);
        foreach ($setterFields as $denormalizedFieldName) {
            try {
                $fieldName = $this->nameConverter->normalize($denormalizedFieldName, $type, $format, $context);
            } catch (Throwable $throwable) {
                $errors->addThrowable($denormalizedFieldName, $throwable);
                continue;
            }
            if (!array_key_exists($fieldName, $data)) {
                continue;
            }
            $succeeded = false;
            $foundErrors = [];
            foreach ($objectAccess->getSetterTypes($reflClass, $denormalizedFieldName) as $getterType) {
                try {
                    $result = $this->denormalizeType($data, $denormalizedFieldName, $fieldName, $getterType, $format, $context);
                    $objectAccess->setValue($object, $denormalizedFieldName, $result);
                    $succeeded = true;
                } catch (Throwable $throwable) {
                    $foundErrors[] = $throwable;
                }
            }
            if (!$succeeded) {
                if ($foundErrors) {
                    $errors->addThrowable($denormalizedFieldName, reset($foundErrors));
                } else {
                    try {
                        $objectAccess->setValue($object, $denormalizedFieldName, $data[$fieldName]);
                    } catch (Throwable $throwable) {
                        $errors->addThrowable($denormalizedFieldName, $throwable);
                    }
                }
            }
        }
        $errors = $errors->getErrors();
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
        return $object;
    }

    private function denormalizeType(array $data, string $denormalizedFieldName, string $fieldName, Type $type, ?string $format = null, array $context = [])
    {
        if (null === ($data[$fieldName] ?? null) && $type->isNullable()) {
            return null;
        }
        switch ($type->getBuiltinType()) {
            case Type::BUILTIN_TYPE_INT:
                return Utils::toInt($data[$fieldName]);
            case Type::BUILTIN_TYPE_FLOAT:
                return Utils::toFloat($data[$fieldName]);
            case Type::BUILTIN_TYPE_STRING:
                return Utils::toString($data[$fieldName]);
            case Type::BUILTIN_TYPE_BOOL:
                return Utils::toBool($data[$fieldName]);
            case Type::BUILTIN_TYPE_OBJECT:
                $newContext = $context;
                $newContext['key_prefix'] = $context['key_prefix'] ? ($context['key_prefix'] . '.' . $denormalizedFieldName) : $denormalizedFieldName;
                return $this->serializer->denormalize(
                    $data[$fieldName],
                    $type->getClassName() ?? 'stdClass',
                    $format,
                    $newContext
                );
            case Type::BUILTIN_TYPE_ARRAY:
                $subType = $type->getCollectionValueType();
                if ($subType && $subType->getClassName()) {
                    $newContext = $context;
                    $newContext['key_prefix'] = $context['key_prefix'] ? ($context['key_prefix'] . '.' . $denormalizedFieldName) : $denormalizedFieldName;
                    return $this->serializer->denormalize(
                        $data[$fieldName],
                        $subType->getClassName() . '[]',
                        $format,
                        $newContext
                    );
                }
                return (array) $data[$fieldName];
            default:
                throw new CouldNotConvertException('int, float, string, bool, object, array', $type->getBuiltinType());
        }
    }

    private function instantiate(array $data, string $type, ObjectAccessInterface $objectAccess, ?string $format = null, array $context = [])
    {
        $reflectionClass = new ReflectionClass($type);
        $argumentTypes = $objectAccess->getConstructorArguments($reflectionClass);
        $errors = new ErrorBag($context['key_prefix']);
        $parsedArguments = [];
        foreach ($argumentTypes as $denormalizedFieldName => $argumentType) {
            try {
                $fieldName = $this->nameConverter->normalize($denormalizedFieldName, $type, $format, $context);
                if (!array_key_exists($fieldName, $data)) {
                    $constructor = $reflectionClass->getConstructor();
                    foreach ($constructor->getParameters() as $parameter) {
                        if ($parameter->name === $denormalizedFieldName && $parameter->isDefaultValueAvailable()) {
                            $parsedArguments[] = $parameter->getDefaultValue();
                            continue(2);
                        }
                    }
                    throw new MissingConstructorArgumentsException($fieldName . ' is required');
                }
                if ($argumentType) {
                    $parsedArguments[] = $this->denormalizeType($data, $denormalizedFieldName, $fieldName, $argumentType, $format, $context);
                } else {
                    $parsedArguments[] = $data[$fieldName];

                }
            } catch (Throwable $throwable) {
                $errors->addThrowable($denormalizedFieldName, $throwable);
            }
        }
        $errors = $errors->getErrors();
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
        $reflClass = new ReflectionClass($type);
        return $objectAccess->instantiate($reflClass, $parsedArguments);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return (is_array($data) || $data instanceof stdClass) && class_exists($type);
    }

    public function normalize($object, $format = null, array $context = [])
    {
        $context = $this->sanitizeContext($context);
        /** @var ObjectAccessInterface $objectAccess */
        $objectAccess = $context['object_access'];
        $reflectionClass = new ReflectionClass($object);
        if ($this->classMetadataFactory && isset($context['groups'])) {
            $objectAccess = $this->filterObjectAccess($objectAccess, $reflectionClass->name, $context['groups']);
        }
        $result = [];
        foreach ($objectAccess->getGetterFields($reflectionClass) as $denormalizedFieldName) {
            $fieldName = $this->nameConverter->normalize($denormalizedFieldName, $reflectionClass->name, $format, $context);
            $result[$fieldName] = $this->toPrimitive($objectAccess->getValue($object, $denormalizedFieldName), $fieldName, $format, $context);
        }
        return $result;
    }

    private function filterObjectAccess(ObjectAccessInterface $objectAccess, string $className, array $groups): ObjectAccessInterface
    {
        $allowedAttributes = [];
        foreach ($this->classMetadataFactory->getMetadataFor($className)->getAttributesMetadata() as $attributeMetadata) {
            $name = $attributeMetadata->getName();

            if (array_intersect($attributeMetadata->getGroups(), $groups)) {
                $allowedAttributes[] = $name;
            }
        }

        return new FilteredObjectAccess($objectAccess, $allowedAttributes);
    }

    private function toPrimitive($input, string $fieldName, ?string $format = null, array $context)
    {
        if (is_array($input)) {
            $result = [];
            foreach ($input as $key => $item) {
                $newContext = $context;
                $newContext['object_hierarchy'][] = $input;
                $newContext['key_prefix'] .= '.' . $fieldName . '.' . $key;
                $result[$key] = $this->toPrimitive($item, $key, $format, $newContext);
            }
            return $result;
        }
        if (is_object($input)) {
            $newContext = $context;
            $newContext['object_hierarchy'][] = $input;
            $newContext['key_prefix'] .= '.' . $fieldName;
            return $this->serializer->normalize($input, $format, $newContext);
        }
        return $input;
    }

    public function supportsNormalization($data, $format = null)
    {
        return is_object($data);
    }

    private function sanitizeContext(array $context): array
    {
        if (empty($context['object_access'])) {
            $context['object_access'] = $this->objectAccess;
        }
        if (empty($context['key_prefix'])) {
            $context['key_prefix'] = '';
        }
        if (empty($context['object_hierarchy'])) {
            $context['object_hierarchy'] = [];
        }
        return $context;
    }
}
