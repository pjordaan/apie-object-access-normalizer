<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;

use Throwable;
use W2w\Lib\ApieObjectAccessNormalizer\Errors\ErrorBag;
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
     * @param string[][]|ErrorBag $errors
     * @param Throwable|null $previous
     */
    public function __construct($errors, Throwable $previous = null)
    {
        $this->errors = $errors instanceof ErrorBag ? $errors->getErrors() : (array) $errors;
        if (!$previous && $errors instanceof ErrorBag && $errors->hasErrors()) {
            $tmp = $errors->getExceptions();
            $tmp = reset($tmp);
            if ($tmp) {
                $previous = reset($tmp) ? : null;
            }
        }
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
