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
 * @version   SVN: $Id$
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
     * @param string $sId
     * @param bool   $mDefaultValue
     */
    public function __construct($sId, $mDefaultValue = false)
    {
        parent::__construct($sId, $mDefaultValue);
    }

    /**
     * Legacy converter for legacy values.
     *
     * @param $mValue
     *
     * @return bool
     */
    protected function _stringToBoolConverter($mValue)
    {
        if ($mValue === 'true') {
            $mValue = true;
        } elseif ($mValue === 'false') {
            $mValue = false;
        }

        return $mValue;
    }

    /**
     * Legacy wrapper for old config values.
     *
     * @param mixed $mValue
     */
    public function setValue($mValue)
    {
        $mValue = $this->_stringToBoolConverter($mValue);
        parent::setValue($mValue);
    }

    /**
     * Checks if the given value is bool.
     *
     * @param mixed $mValue
     *
     * @return bool
     */
    public function isValidValue($mValue)
    {
        return is_bool($mValue) === true;
    }
}