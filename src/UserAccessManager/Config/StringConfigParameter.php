<?php
/**
 * StringConfigParameter.php
 *
 * The StringConfigParameter class file.
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

class StringConfigParameter extends ConfigParameter
{
    /**
     * StringConfigParameter constructor.
     *
     * @param string $sId
     * @param string $mDefaultValue
     */
    public function __construct($sId, $mDefaultValue = '')
    {
        parent::__construct($sId, $mDefaultValue);
    }

    /**
     * Checks if the given value is a string.
     *
     * @param mixed $mValue
     *
     * @return bool
     */
    public function isValidValue($mValue)
    {
        return is_string($mValue) === true;
    }
}
