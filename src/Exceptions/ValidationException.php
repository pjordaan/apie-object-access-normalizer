<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;

use Throwable;
use W2w\Lib\ApieObjectAccessNormalizer\Normalizers\ApieObjectAccessNormalizer;

/**
 * Exception thrown if the constructor could not be called or if a setter threw an error.
 *
 * @see ApieObjectAccessNormalizer::denormalize()
 */
class ValidationException extends ApieException
{
    private $errors;

    /**
     * @param string[][] $errors
     * @param Throwable|null $previous
     */
    public function __construct(array $errors, Throwable $previous = null)
    {
        $this->errors = $errors;
        parent::__construct(422, 'A validation error occurred', $previous);
    }

    /**
     * Returns the validation errors.
     *
     * @return string[][]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
