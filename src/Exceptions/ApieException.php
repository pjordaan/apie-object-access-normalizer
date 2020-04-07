<?php

namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Base class that is extended by all Apie classes.
 */
abstract class ApieException extends HttpException
{
}
