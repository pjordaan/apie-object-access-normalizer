<?php
namespace W2w\Test\ApieObjectAccessNormalizer\Mocks\TestCase2;

use RuntimeException;

class ValidationException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A validation error occured');
    }

    public function getErrors()
    {
        return [
            'field' => 'error 1',
            'field 2' => 'error 2',
        ];
    }
}
