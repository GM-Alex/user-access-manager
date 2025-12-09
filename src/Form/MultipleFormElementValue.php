<?php

declare(strict_types=1);

namespace UserAccessManager\Form;

use Exception;

class MultipleFormElementValue extends ValueSetFormElementValue
{
    private ?FormElement $subElement = null;

    /**
     * @throws Exception
     */
    public function setSubElement(FormElement $subElement): void
    {
        if ($subElement instanceof MultipleFormElement) {
            throw new Exception('Invalid form type for sub element.');
        }

        $this->subElement = $subElement;
    }

    public function getSubElement(): ?FormElement
    {
        return $this->subElement;
    }
}
