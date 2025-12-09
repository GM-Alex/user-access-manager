<?php

declare(strict_types=1);

namespace UserAccessManager\Form;

class ValueSetFormElementValue
{
    use ValueTrait;
    use LabelTrait;

    public function __construct(mixed $value, string $label)
    {
        $this->value = $value;
        $this->label = $label;
    }

    /**
     * @var bool
     */
    private bool $isDisabled = false;

    public function markDisabled(): void
    {
        $this->isDisabled = true;
    }

    public function isDisabled(): bool
    {
        return $this->isDisabled;
    }
}
