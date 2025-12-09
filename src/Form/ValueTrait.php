<?php

declare(strict_types=1);

namespace UserAccessManager\Form;

trait ValueTrait
{
    protected mixed $value;

    public function getValue(): mixed
    {
        return $this->value;
    }
}
