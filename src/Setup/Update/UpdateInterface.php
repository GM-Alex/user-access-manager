<?php

declare(strict_types=1);

namespace UserAccessManager\Setup\Update;

interface UpdateInterface
{
    public function getVersion(): string;

    public function update(): bool;
}
