<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;


interface LocalizationableException
{
    public function getI18n(): LocalizationInfo;
}
