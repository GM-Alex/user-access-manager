<?php

declare(strict_types=1);

namespace UserAccessManager\Config;

use Exception;

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
        $map = array_flip($this->selections);
        return (isset($map[$value]) === true);
    }

    public function getSelections(): array
    {
        return $this->selections;
    }
}
