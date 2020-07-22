<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;

use W2w\Lib\ApieObjectAccessNormalizer\Errors\ErrorBag;

/**
 * Used nu ErrprBag and ValidationException to link errors.
 *
 * @see ErrorBag::addThrowable()
 * @see ValidationException::getErrorBag()
 *
 * @internal
 */
interface ErrorBagAwareException
{
    public function getErrorBag(): ErrorBag;
}
