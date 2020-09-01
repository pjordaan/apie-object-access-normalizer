<?php

namespace W2w\Test\ApieObjectAccessNormalizer\Mocks;

class LocalizationAwareClass
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var bool
     */
    private $private;

    private $description = [];

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setDescription(string $locale, string $description): self
    {
        $this->description[$locale] = $description;

        return $this;
    }

    public function getDescription(string $locale): ?string
    {
        return $this->description[$locale] ?? null;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }

    public function setPrivate(bool $private): self
    {
        $this->private = $private;

        return $this;
    }
}
