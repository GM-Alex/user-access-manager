<?php

declare(strict_types=1);

namespace UserAccessManager\Config\Parameter;

use Exception;
use UserAccessManager\Config\Config;

class SelectionConfigParameter extends ConfigParameter
{
    /**
     * @throws Exception
     */
    public function __construct(string $id, mixed $defaultValue, private array $selections)
    {
        parent::__construct($id, $defaultValue);
    }

    public function isValidValue(mixed $value): bool
    {
        return isset(array_flip($this->selections)[$value]);
    }

    public function getSelections(): array
    {
        return $this->selections;
    }
}
