<?php

declare(strict_types=1);

namespace UserAccessManager\Form\Element;

use UserAccessManager\Form\Form;

class ValueSetFormElementValue
{
    use ValueTrait;
    use LabelTrait;

    private bool $isDisabled = false;

    public function __construct(mixed $value, string $label)
    {
        $this->value = $value;
        $this->label = $label;
    }

    public function markDisabled(): void
    {
        $this->isDisabled = true;
    }

    public function isDisabled(): bool
    {
        return $this->isDisabled;
    }
}
