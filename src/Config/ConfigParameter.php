<?php

declare(strict_types=1);

namespace UserAccessManager\Config;

use Exception;

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
    protected function validateValue($value): void
    {
        if ($this->isValidValue($value) === false) {
            throw new Exception("Wrong value '$value' type given for '$this->id'.'");
        }
    }

    public function setValue(mixed $value): void
    {
        $this->isValidValue($value);
        $this->value = $value;
    }

    public function getValue(): mixed
    {
        return ($this->value === null) ? $this->defaultValue : $this->value;
    }
}
