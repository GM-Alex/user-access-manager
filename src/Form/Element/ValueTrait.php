<?php

declare(strict_types=1);

namespace UserAccessManager\Form\Element;

use UserAccessManager\Form\Form;

trait ValueTrait
{
    protected mixed $value;

    public function getValue(): mixed
    {
        return $this->value;
    }
}
