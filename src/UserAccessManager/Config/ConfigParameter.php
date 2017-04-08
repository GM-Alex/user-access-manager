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
 * @version   SVN: $Id$
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
    protected $sId;

    /**
     * @var mixed
     */
    protected $mDefaultValue = null;

    /**
     * @var mixed
     */
    protected $mValue = null;

    /**
     * ConfigParameter constructor.
     *
     * @param string $sId
     * @param mixed  $mDefaultValue
     */
    public function __construct($sId, $mDefaultValue = null)
    {
        $this->sId = $sId;

        $this->validateValue($mDefaultValue);
        $this->mDefaultValue = $mDefaultValue;
    }

    /**
     * Returns the id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->sId;
    }

    /**
     * Checks the value type and throws an exception if the value isn't the required type.
     *
     * @param mixed $mValue
     *
     * @throws \Exception
     */
    protected function validateValue($mValue)
    {
        if ($this->isValidValue($mValue) === false) {
            throw new \Exception("Wrong value '{$mValue}' type given for '{$this->sId}'.'");
        }
    }

    /**
     * Sets the current value.
     *
     * @param mixed $mValue
     */
    public function setValue($mValue)
    {
        $this->isValidValue($mValue);
        $this->mValue = $mValue;
    }

    /**
     * Returns the current parameter value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return ($this->mValue === null) ? $this->mDefaultValue : $this->mValue;
    }
}
