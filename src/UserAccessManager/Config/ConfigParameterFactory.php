<?php
/**
 * ConfigParameterFactory.php
 *
 * The ConfigParameterFactory class file.
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
 * Class ConfigParameterFactory
 *
 * @package UserAccessManager\Config
 */
class ConfigParameterFactory
{
    /**
     * Creates a boolean config parameter object.
     *
     * @param string $sId
     * @param mixed  $mDefaultValue
     *
     * @return BooleanConfigParameter
     */
    public function createBooleanConfigParameter($sId, $mDefaultValue = false)
    {
        return new BooleanConfigParameter($sId, $mDefaultValue);
    }

    /**
     * Creates a selection config parameter object.
     *
     * @param string $sId
     * @param mixed  $mDefaultValue
     * @param array  $aSelection
     *
     * @return SelectionConfigParameter
     */
    public function createSelectionConfigParameter($sId, $mDefaultValue, array $aSelection)
    {
        return new SelectionConfigParameter($sId, $mDefaultValue, $aSelection);
    }

    /**
     * Creates a string config parameter object.
     *
     * @param string $sId
     * @param mixed  $mDefaultValue
     *
     * @return StringConfigParameter
     */
    public function createStringConfigParameter($sId, $mDefaultValue = '')
    {
        return new StringConfigParameter($sId, $mDefaultValue);
    }
}