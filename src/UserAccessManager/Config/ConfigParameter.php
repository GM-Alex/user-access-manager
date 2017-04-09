<?php
/**
 * ConfigParameter.php
 *
 * The ConfigParameter class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Config;

/**
 * Class ConfigParameter
 *
 * @package UserAccessManager\Config
 */
abstract class ConfigParameter implements ConfigParameterInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var mixed
     */
    protected $defaultValue = null;

    /**
     * @var mixed
     */
    protected $value = null;

    /**
     * ConfigParameter constructor.
     *
     * @param string $id
     * @param mixed  $defaultValue
     */
    public function __construct($id, $defaultValue = null)
    {
        $this->id = $id;

        $this->validateValue($defaultValue);
        $this->defaultValue = $defaultValue;
    }

    /**
     * Returns the id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Checks the value type and throws an exception if the value isn't the required type.
     *
     * @param mixed $value
     *
     * @throws \Exception
     */
    protected function validateValue($value)
    {
        if ($this->isValidValue($value) === false) {
            throw new \Exception("Wrong value '{$value}' type given for '{$this->id}'.'");
        }
    }

    /**
     * Sets the current value.
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->isValidValue($value);
        $this->value = $value;
    }

    /**
     * Returns the current parameter value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return ($this->value === null) ? $this->defaultValue : $this->value;
    }
}
