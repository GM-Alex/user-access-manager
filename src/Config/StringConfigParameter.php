<?php

declare(strict_types=1);

namespace UserAccessManager\Config;

use Exception;

class StringConfigParameter extends ConfigParameter
{
    /**
     * @throws Exception
     */
    public function __construct(string $id, $defaultValue = '')
    {
        parent::__construct($id, $defaultValue);
    }

    public function isValidValue(mixed $value): bool
    {
        return is_string($value) === true;
    }
}
