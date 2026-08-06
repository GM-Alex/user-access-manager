<?php

declare(strict_types=1);

namespace UserAccessManager\Form\Element;

use UserAccessManager\Form\Form;

abstract class ValueSetFormElement extends FormElement
{
    /**
     * @param ValueSetFormElementValue[] $possibleValues
     */
    public function __construct(
        string $id,
        protected array $possibleValues,
        mixed $value = null,
        ?string $label = null,
        ?string $description = null
    ) {
        parent::__construct($id, $value, $label, $description);

        $keys = array_map(
            fn(ValueSetFormElementValue $possibleValue) => $possibleValue->getValue(),
            $possibleValues
        );

        $this->possibleValues = array_combine($keys, $possibleValues);
    }

    /**
     * @return ValueSetFormElementValue[]
     */
    public function getPossibleValues(): array
    {
        return $this->possibleValues;
    }
}
