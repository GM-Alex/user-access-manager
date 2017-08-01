<?php
namespace UserAccessManager\Form;

/**
 * Trait valueTrait
 *
 * @package UserAccessManager\Form
 */
trait ValueTrait
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }
}
