<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\Interfaces;


interface LocalizationAwareInterface
{
    public function getAcceptLanguage(): ?string;
    public function getContentLanguage(): ?string;
}
