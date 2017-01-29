<?php
/**
 * FileProtectionInterface.php
 *
 * The FileProtectionInterface interface file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\FileHandler;

use UserAccessManager\Config\Config;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Interface FileProtectionInterface
 *
 * @package UserAccessManager\FileProtection
 */
interface FileProtectionInterface
{
    /**
     * FileProtectionInterface constructor.
     *
     * @param Wordpress   $oWrapper
     * @param Config      $oConfig
     * @param Util        $oUtil
     */
    public function __construct(Wordpress $oWrapper, Config $oConfig, Util $oUtil);

    /**
     * Creates the file protection.
     *
     * @param string $sDir
     * @param string $sObjectType
     *
     * @return bool
     */
    public function create($sDir, $sObjectType = null);

    /**
     * Deletes the file protection.
     *
     * @param string $sDir
     *
     * @return bool
     */
    public function delete($sDir);
}