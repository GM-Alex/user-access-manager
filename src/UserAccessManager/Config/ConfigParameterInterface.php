<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 18.01.17
 * Time: 23:06
 */

namespace UserAccessManager\Config;


interface ConfigParameterInterface
{
    /**
     * Validates the value.
     *
     * @param mixed $mValue
     *
     * @return bool
     */
    public function isValidValue($mValue);

    /**
     * Sets the current value.
     *
     * @param mixed $mValue
     */
    public function setValue($mValue);

    /**
     * Returns the current parameter value.
     *
     * @return mixed
     */
    public function getValue();
}