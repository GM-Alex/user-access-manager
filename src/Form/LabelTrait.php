<?php
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
    public function getLabel()
    {
        return $this->label;
    }
}
