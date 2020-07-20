<?php


namespace W2w\Lib\ApieObjectAccessNormalizer\Exceptions;


final class LocalizationInfo
{
    /**
     * @var string
     */
    private $messageString;

    /**
     * @var array
     */
    private $replacements;

    /**
     * @var int
     */
    private $amount;

    public function __construct(string $messageString, array $replacements = [], int $amount = 1)
    {
        $this->messageString = $messageString;
        $this->replacements = $replacements;
        $this->amount = $amount;
    }

    /**
     * Get the message string.
     *
     * @return string
     */
    public function getMessageString(): string
    {
        return $this->messageString;
    }

    /**
     * Get the replacements.
     *
     * @return array
     */
    public function getReplacements(): array
    {
        return $this->replacements;
    }

    /**
     * Get the amount.
     *
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }
}
