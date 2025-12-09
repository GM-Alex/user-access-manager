<?php

declare(strict_types=1);

namespace UserAccessManager\Form;

trait LabelTrait
{
    protected ?string $label;

    public function getLabel(): ?string
    {
        return $this->label;
    }
}
