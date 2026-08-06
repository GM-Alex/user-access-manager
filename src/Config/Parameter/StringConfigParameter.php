<?php

declare(strict_types=1);

namespace UserAccessManager\Config\Parameter;

use Exception;
use UserAccessManager\Config\Config;

class StringConfigParameter extends ConfigParameter
{
    /**
     * @throws Exception
     */
    public function __construct(string $id, mixed $defaultValue = '')
    {
        parent::__construct($id, $defaultValue);
    }

    public function isValidValue(mixed $value): bool
    {
        return is_string($value);
    }
}
