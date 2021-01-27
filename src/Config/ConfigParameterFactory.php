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
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

declare(strict_types=1);

namespace UserAccessManager\Config;

use Exception;

/**
 * Class ConfigParameterFactory
 *
 * @package UserAccessManager\Config
 */
class ConfigParameterFactory
{
    /**
     * Creates a boolean config parameter object.
     * @param string $id
     * @param mixed $defaultValue
     * @return BooleanConfigParameter
     * @throws Exception
     */
    public function createBooleanConfigParameter(string $id, $defaultValue = false): BooleanConfigParameter
    {
        return new BooleanConfigParameter($id, $defaultValue);
    }

    /**
     * Creates a selection config parameter object.
     * @param string $id
     * @param mixed $defaultValue
     * @param array $selection
     * @return SelectionConfigParameter
     * @throws Exception
     */
    public function createSelectionConfigParameter(
        string $id,
        $defaultValue,
        array $selection
    ): SelectionConfigParameter {
        return new SelectionConfigParameter($id, $defaultValue, $selection);
    }

    /**
     * Creates a string config parameter object.
     * @param string $id
     * @param mixed $defaultValue
     * @return StringConfigParameter
     * @throws Exception
     */
    public function createStringConfigParameter(string $id, $defaultValue = ''): StringConfigParameter
    {
        return new StringConfigParameter($id, $defaultValue);
    }
}
