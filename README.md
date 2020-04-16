# apie-object-access-normalizer
[![CircleCI](https://circleci.com/gh/pjordaan/apie-object-access-normalizer.svg?style=svg)](https://circleci.com/gh/pjordaan/apie-object-access-normalizer)
[![codecov](https://codecov.io/gh/pjordaan/apie/branch/master/graph/badge.svg)](https://codecov.io/gh/pjordaan/apie-object-access-normalizer/)
[![Travis](https://api.travis-ci.org/pjordaan/apie-object-access-normalizer.svg?branch=master)](https://travis-ci.org/pjordaan/apie-object-access-normalizer)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/pjordaan/apie-object-access-normalizer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/pjordaan/apie-object-access-normalizer/?branch=master)

Object access normalizer used internally by apie. It can be used outside Apie with the symfony serializer
where it can replace the default object normalizers that are used there.


## Usage with Symfony Serializer
The simplest usage is adding ApieObjectAccessNormalizer to the constructor of the Symfony serializer.
```php
<?php
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use W2w\Lib\ApieObjectAccessNormalizer\Normalizers\ApieObjectAccessNormalizer;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\ObjectAccess;

$serializer = new Serializer(
    [
        new DateTimeNormalizer(),
        new ApieObjectAccessNormalizer(),
        new ArrayDenormalizer(),
    ],
    [new JsonEncoder()]
);

class Example
{
    private $number;
    
    private $stringValue = '<no value set>';
    
    public function __construct(int $number)
    {
        $this->number = $number;
    }
    
    public function setStringValue(string $stringValue)
    {
        $this->stringValue = $stringValue;
    }
    
    public function getNumber(): int 
    {
        return $this->number;
    }
    
    public function getStringValue(): string
    {
        return $this->stringValue;
    }
}

$instance = new Example(12);
// returns array['number' => 12, 'stringValue' => '<no value set>']
var_dump($serializer->serialize($instance, 'json'));
// returns new Example(12)
var_dump($serializer->deserialize(['number' => 12], Example::class, 'json'));
// throws validation error with errors => ['number' =>' must be one of "int" ("invalid" given)']
$serializer->deserialize(['number' => 'invalid'], Example::class, 'json');
// calls setStringValue("blah") on $instance
$serializer->deserialize(['stringValue' => 'text'], Example::class, 'json', ['object_to_populate' => $instance]);
// use a different object access on $instance to set private properties that have no public setter.
$serializer->deserialize(['number' => '15'], Example::class, 'json', ['object_to_populate' => $instance, 'object_access' => new ObjectAccess(false)]);
```
Unless the context option 'object_to_populate' is called it will first try to create a new object by reading
the constructor arguments. Afterwards it will check all setters. If a setter throws an error the error is considered
a validation error and will return a validation exception with errors structure. 

ObjectAccess is also able to read property typehints in PHP 7.4+ and php docblocks with composer package
phpdocumentor/reflection-docblock.

## Camel case keys
By default the property name is the same as the key. We can override this in the ApieObjectAccessNormalizer class by providing 
a class implementing Symfony\Component\Serializer\NameConverter\NameConverterInterface.

```php
<?php
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;
use W2w\Lib\ApieObjectAccessNormalizer\Normalizers\ApieObjectAccessNormalizer;
use W2w\Lib\ApieObjectAccessNormalizer\ObjectAccess\ObjectAccess;

$serializer = new Serializer(
    [
        new DateTimeNormalizer(),
        new ApieObjectAccessNormalizer(new ObjectAccess(), new CamelCaseToSnakeCaseNameConverter),
        new ArrayDenormalizer(),
    ],
    [new JsonEncoder()]
);
$instance = new Example(12);
// returns array['number' => 12, 'string_value' => '<no value set>']
var_dump($serializer->serialize($instance, 'json'));
```

### in Symfony framework
If you want to use it in the Symfony framework all you need to do is register class
W2w\Lib\ApieObjectAccessNormalizer\Normalizers\ApieObjectAccessNormalizer
as a service and tag it with 'serializer.normalizer' to add it to the symfony serializer.

