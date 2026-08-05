<?php

declare(strict_types=1);

namespace UserAccessManager\Config\Parameter;

use Exception;
use UserAccessManager\Config\Config;

abstract class ConfigParameter implements ConfigParameterInterface
{
    protected mixed $defaultValue = null;
    protected mixed $value = null;

    /**
     * @throws Exception
     */
    public function __construct(
        protected string $id,
        mixed $defaultValue = null
    ) {
        $this->validateValue($defaultValue);
        $this->defaultValue = $defaultValue;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @throws Exception
     */
    protected function validateValue(mixed $value): void
    {
        if ($this->isValidValue($value) === false) {
            throw new Exception("Wrong value '$value' type given for '$this->id'.'");
        }
    }

    /**
     * Deliberately stores the value unvalidated. Config replays every persisted option through here,
     * and a selection's valid set is environment-dependent (active_cache_provider only lists Redis
     * while the object cache drop-in is present). Rejecting an unrecognised value would make the next
     * settings save write the default over it, because setConfigParameters() persists every parameter.
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getValue(): mixed
    {
        return ($this->value === null) ? $this->defaultValue : $this->value;
    }
}
