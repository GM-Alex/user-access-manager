<?php

declare(strict_types=1);

namespace UserAccessManager\Form;

/**
 * Trait labelTrait
 *
 * @package UserAccessManager\Form
 */
trait LabelTrait
{
    /**
     * @var string
     */
    protected $label;

    /**
     * @return null|string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }
}
