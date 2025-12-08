<?php

declare(strict_types=1);

namespace UserAccessManager\File;

class FileObjectFactory
{
    public function createFileObject(int|string $id, string $type, string $file, bool $isImage = false): FileObject
    {
        return new FileObject($id, $type, $file, $isImage);
    }
}
