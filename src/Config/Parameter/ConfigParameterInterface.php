<?php

declare(strict_types=1);

namespace UserAccessManager\Config\Parameter;

use UserAccessManager\Config\Config;

interface ConfigParameterInterface
{
    public function isValidValue(mixed $value): bool;

    public function setValue(mixed $value);

    public function getValue(): mixed;
}
