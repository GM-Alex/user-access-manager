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
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Config;

class StringConfigParameter extends ConfigParameter
{
    /**
     * StringConfigParameter constructor.
     *
     * @param string $id
     * @param string $defaultValue
     */
    public function __construct($id, $defaultValue = '')
    {
        parent::__construct($id, $defaultValue);
    }

    /**
     * Checks if the given value is a string.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isValidValue($value)
    {
        return is_string($value) === true;
    }
}
