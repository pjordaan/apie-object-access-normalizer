<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\Errors;

use ReflectionClass;
use Throwable;
use W2w\Lib\ApieObjectAccessNormalizer\Normalizers\ApieObjectAccessNormalizer;

/**
 * Maps all found exceptions to an error map.
 *
 * @internal
 * @see ApieObjectAccessNormalizer
 */
class ErrorBag
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string[][]
     */
    private $errors = [];

    /**
     * @var Throwable[][]
     */
    private $exceptions = [];

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Adds error messages to the errors from an exception/error.
     * If it is a validation error, the mapping is taken over.
     *
     * @param string $fieldName
     * @param Throwable $throwable
     */
    public function addThrowable(string $fieldName, Throwable $throwable)
    {
        $prefix = $this->prefix ? ($this->prefix . '.' . $fieldName) : $fieldName;
        if ($validationErrors = $this->extractValidationErrors($throwable)) {
            $expectedPrefix = $prefix . '.';
            foreach ($validationErrors as $key => $validationError) {
                if ($key !== $prefix && strpos($key, $expectedPrefix) !== 0) {
                    $key = $expectedPrefix . $key;
                }
                if (!is_array($validationError)) {
                    $validationError = [$validationError];
                }
                foreach ($validationError as $error) {
                    $this->errors[$key][] = $error;
                    $this->exceptions[$key][] = $throwable;
                }
            }
            return;
        }
        $this->errors[$prefix][] = $throwable->getMessage();
        $this->exceptions[$prefix][] = $throwable;
    }

    /**
     * Returns a list of error messages.
     *
     * @return string[][]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Since ApieObjectAccessNormalizer catches all exceptions for debugging reasons we keep a record of the exceptions
     * too.
     *
     * @return array
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    /**
     * Tries to guess if the class is a validationexception for any arbitrary library. Most popular libraries
     * just have a ValidationException in use.
     *
     * @param Throwable $throwable
     * @return array|null
     */
    private function extractValidationErrors(Throwable $throwable): ?array
    {
        $refl = new ReflectionClass($throwable);
        if ($refl->getShortName() === 'ValidationException') {
            if (method_exists($throwable, 'getErrors')) {
                return (array) $throwable->getErrors();
            }
            if (method_exists($throwable, 'getError')) {
                return [$throwable->getError()];
            }
            if (method_exists($throwable, 'errors')) {
                return (array) $throwable->errors();
            }
        }
        return null;
    }
}
