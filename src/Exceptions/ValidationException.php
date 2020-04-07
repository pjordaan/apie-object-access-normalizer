<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;

use Throwable;

class ValidationException extends ApieException
{
    private $errors;

    public function __construct(array $errors, Throwable $previous = null)
    {
        $this->errors = $errors;
        parent::__construct(422, 'A validation error occurred', $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
