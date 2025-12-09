<?php

declare(strict_types=1);

namespace UserAccessManager\Form;

use Exception;

abstract class MultipleFormElement extends ValueSetFormElement
{
    /**
     * @param MultipleFormElementValue[] $possibleValues
     * @throws Exception
     */
    public function __construct(
        string $id,
        array $possibleValues,
        mixed $value = null,
        ?string $label = null,
        ?string $description = null
    ) {
        foreach ($possibleValues as $possibleValue) {
            if (($possibleValue instanceof MultipleFormElementValue) === false) {
                throw new Exception('Values must be MultipleFormElementValue objects');
            }
        }

        parent::__construct($id, $possibleValues, $value, $label, $description);
    }

    /**
     * @return MultipleFormElementValue[]
     */
    public function getPossibleValues(): array
    {
        return $this->possibleValues;
    }
}
