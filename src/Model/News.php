<?php

namespace src\Model;

class News
{
    protected int $id;
    protected string $name;
    protected string $shortText;
    protected string $fullText;
    protected int $isDeleted;

    public function __construct(int $id = 0)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getShortText(): string
    {
        return $this->shortText;
    }

    public function setShortText(string $shortText): void
    {
        $this->shortText = $shortText;
    }

    public function getFullText(): string
    {
        return $this->fullText;
    }

    public function setFullText(string $fullText): void
    {
        $this->fullText = $fullText;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function Delete(): void
    {
        $this->isDeleted = 1;
    }
}
