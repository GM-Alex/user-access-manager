<?php
/**
 * ConfigParameterInterface.php
 *
 * The ConfigParameterInterface interface file.
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
 * Interface ConfigParameterInterface
 *
 * @package UserAccessManager\Config
 */
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