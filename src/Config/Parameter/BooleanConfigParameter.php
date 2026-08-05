<?php

declare(strict_types=1);

namespace UserAccessManager\Config\Parameter;

use Exception;
use UserAccessManager\Config\Config;

class BooleanConfigParameter extends ConfigParameter
{
    /**
     * @throws Exception
     */
    public function __construct(string $id, mixed $defaultValue = false)
    {
        parent::__construct($id, $defaultValue);
    }

    private function convertToBoolean(mixed $value): mixed
    {
        if (in_array($value, [1, '1', 'true'], true)) {
            $value = true;
        } elseif (in_array($value, [0, '0', 'false'], true)) {
            $value = false;
        }

        return $value;
    }

    public function setValue(mixed $value): void
    {
        $value = (bool) $this->convertToBoolean($value);
        parent::setValue($value);
    }

    public function isValidValue(mixed $value): bool
    {
        return is_bool($value);
    }
}
