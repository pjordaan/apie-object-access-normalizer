<?php
namespace W2w\Test\ApieObjectAccessNormalizer\Mocks\TestCase4;

use RuntimeException;

class ValidationException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A validation error occured');
    }

    public function errors()
    {
        return [
            'field' => ['error 1', 'error 2'],
            'field 2' => ['error 3'],
        ];
    }
}
