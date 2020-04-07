<?php
namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;

class NameNotFoundException extends ApieException
{
    public function __construct(string $name)
    {
        parent::__construct(500, 'Name "' . $name . '" not found!"');
    }
}
