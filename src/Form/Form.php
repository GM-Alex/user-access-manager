<?php

declare(strict_types=1);

namespace UserAccessManager\Form;

class Form
{
    /**
     * @var FormElement[]
     */
    private array $elements = [];

    public function addElement(FormElement $element): void
    {
        $this->elements[$element->getId()] = $element;
    }

    /**
     * @return FormElement[]
     */
    public function getElements(): array
    {
        return $this->elements;
    }
}
