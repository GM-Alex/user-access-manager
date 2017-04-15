<?php
/**
 * BooleanParameter.php
 *
 * The BooleanParameter class file.
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
 * Class BooleanConfigParameter
 * @package UserAccessManager\Config
 */
class BooleanConfigParameter extends ConfigParameter
{
    /**
     * BooleanConfigParameter constructor.
     *
     * @param string $id
     * @param bool   $defaultValue
     */
    public function __construct($id, $defaultValue = false)
    {
        parent::__construct($id, $defaultValue);
    }

    /**
     * Legacy converter for legacy values.
     *
     * @param $value
     *
     * @return bool
     */
    private function stringToBoolConverter($value)
    {
        if ($value === 'true') {
            $value = true;
        } elseif ($value === 'false') {
            $value = false;
        }

        return $value;
    }

    /**
     * Legacy wrapper for old config values.
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        $value = $this->stringToBoolConverter($value);
        parent::setValue($value);
    }

    /**
     * Checks if the given value is bool.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isValidValue($value)
    {
        return is_bool($value) === true;
    }
}
