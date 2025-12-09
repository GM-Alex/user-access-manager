<?php

declare(strict_types=1);

namespace UserAccessManager\Config;

use Exception;

class BooleanConfigParameter extends ConfigParameter
{
    /**
     * @throws Exception
     */
    public function __construct(string $id, $defaultValue = false)
    {
        parent::__construct($id, $defaultValue);
    }

    private function valueToBoolConverter(mixed $value): mixed
    {
        if (in_array($value, [1, '1', 'true'])) {
            $value = true;
        } elseif (in_array($value, [0, '0', 'false'])) {
            $value = false;
        }

        return $value;
    }

    public function setValue(mixed $value): void
    {
        $value = (bool) $this->valueToBoolConverter($value);
        parent::setValue($value);
    }

    public function isValidValue(mixed $value): bool
    {
        return is_bool($value) === true;
    }
}
