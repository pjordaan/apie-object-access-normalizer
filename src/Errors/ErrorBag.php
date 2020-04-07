<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\Errors;

use ReflectionClass;
use Throwable;

class ErrorBag
{
    private $prefix;

    private $errors = [];

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

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
                }
            }
            return;
        }
        $this->errors[$prefix][] = $throwable->getMessage();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

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
