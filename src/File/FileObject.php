<?php

declare(strict_types=1);

namespace UserAccessManager\File;

class FileObject
{
    public function __construct(
        private int|string $id,
        private string $type,
        private string $file,
        private bool $isImage = false
    ) {}

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function isImage(): bool
    {
        return $this->isImage;
    }
}
