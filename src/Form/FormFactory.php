<?php

declare(strict_types=1);

namespace UserAccessManager\Form;

use Exception;

class FormFactory
{
    public function createFrom(): Form
    {
        return new Form();
    }

    public function createValueSetFromElementValue(mixed $value, string $label): ValueSetFormElementValue
    {
        return new ValueSetFormElementValue($value, $label);
    }

    public function createMultipleFormElementValue(mixed $value, string $label): MultipleFormElementValue
    {
        return new MultipleFormElementValue($value, $label);
    }

    public function createInput(
        string $id,
        mixed $value = null,
        ?string $label = null,
        ?string $description = null
    ): Input {
        return new Input($id, $value, $label, $description);
    }

    public function createTextarea(
        string $id,
        mixed $value = null,
        ?string $label = null,
        ?string $description = null
    ): Textarea {
        return new Textarea($id, $value, $label, $description);
    }

    public function createSelect(
        string $id,
        array $possibleValues,
        mixed $value = null,
        ?string $label = null,
        ?string $description = null
    ): Select {
        return new Select($id, $possibleValues, $value, $label, $description);
    }

    /**
     * @throws Exception
     */
    public function createRadio(
        string $id,
        array $possibleValues,
        mixed $value = null,
        ?string $label = null,
        ?string $description = null
    ): Radio {
        return new Radio($id, $possibleValues, $value, $label, $description);
    }
}
