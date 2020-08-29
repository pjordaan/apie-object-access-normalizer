<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\Errors;

use Closure;
use ReflectionClass;
use Throwable;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\ErrorBagAwareException;
use W2w\Lib\ApieObjectAccessNormalizer\Exceptions\LocalizationableException;
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
     * @var ErrorBagField[][]
     */
    private $errors = [];

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @param array $index
     * @param string $prefix
     * @return ErrorBag
     */
    public static function fromArray(array $index, string $prefix = ''): ErrorBag
    {
        $result = new ErrorBag($prefix);
        foreach ($index as $key => $errors) {
            if (!is_array($errors)) {
                $errors = [$errors];
            }
            foreach ($errors as $error) {
                $result->errors[$key][] = new ErrorBagField($error);
            }
        }
        return $result;
    }

    private function merge(ErrorBag $otherBag)
    {
        foreach ($otherBag->errors as $prefix => $errors) {
            foreach ($errors as $error) {
                $this->errors[$prefix][] = $error;
            }
        }
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
        if ($throwable instanceof ErrorBagAwareException) {
            $this->merge($throwable->getErrorBag());
            return;
        }
        if ($validationErrors = $this->extractValidationErrors($throwable)) {
            $expectedPrefix = $prefix . '.';
            foreach ($validationErrors as $key => $validationError) {
                if (('' . $key) !== $prefix && strpos($key, $expectedPrefix) !== 0) {
                    if ($key === '') {
                        $key = $prefix;
                    } else {
                        $key = $expectedPrefix . $key;
                    }
                }
                if (!is_array($validationError)) {
                    $validationError = [$validationError];
                }
                foreach ($validationError as $error) {
                    $this->errors[$key][] = new ErrorBagField($error, null, $throwable);
                }
            }
            return;
        }
        $this->errors[$prefix][] = new ErrorBagField(
            $throwable->getMessage(),
            $throwable instanceof LocalizationableException ? $throwable->getI18n() : null,
            $throwable
        );
    }

    /**
     * Returns a list of error messages.
     *
     * @param Closure|null $callback
     * @return string[][]
     */
    public function getErrors(?Closure $callback = null): array
    {
        if (!$callback) {
            $callback = function (ErrorBagField $field) {
                return $field->getMessage();
            };
        }
        return array_map(
            function (array $errors) use (&$callback) {
                return array_map($callback, $errors);
            },
            $this->errors
        );
    }

    /**
     * Since ApieObjectAccessNormalizer catches all exceptions for debugging reasons we keep a record of the exceptions
     * too.
     *
     * @return Throwable[][]
     */
    public function getExceptions(): array
    {
        return array_map(
            function (array $errors) {
                return array_filter(array_map(
                    function (ErrorBagField $field) {
                        return $field->getSource();
                    },
                    $errors
                ));
            },
            $this->errors
        );
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
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
