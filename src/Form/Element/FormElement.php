<?php

declare(strict_types=1);

namespace UserAccessManager\Form\Element;

use UserAccessManager\Form\Form;

abstract class FormElement
{
    use ValueTrait;
    use LabelTrait;

    public function __construct(
        protected string $id,
        mixed $value = null,
        ?string $label = null,
        protected ?string $description = null
    ) {
        $this->value = $value;
        $this->label = $label;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
