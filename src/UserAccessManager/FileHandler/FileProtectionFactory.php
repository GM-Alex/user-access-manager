<?php
/**
 * FileProtectionFactory.php
 *
 * The FileProtectionFactory class file.
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
 * Class FileProtectionFactory
 *
 * @package UserAccessManager\FileHandler
 */
class FileProtectionFactory
{
    /**
     * @var Wordpress
     */
    protected $_oWordpress;

    /**
     * @var Config
     */
    protected $_oConfig;

    /**
     * @var FileHandler
     */
    protected $_oFileHandler;

    /**
     * @var Util
     */
    protected $_oUtil;

    /**
     * FileProtectionFactory constructor.
     *
     * @param Wordpress $oWordpress
     * @param Config    $oConfig
     * @param Util      $oUtil
     */
    public function __construct(Wordpress $oWordpress, Config $oConfig, Util $oUtil)
    {
        $this->_oWordpress = $oWordpress;
        $this->_oConfig = $oConfig;
        $this->_oUtil = $oUtil;
    }

    /**
     * Returns a new ApacheFileProtection object.
     *
     * @return ApacheFileProtection
     */
    public function createApacheFileProtection()
    {
        return new ApacheFileProtection($this->_oWordpress, $this->_oConfig, $this->_oUtil);
    }

    /**
     * Returns a new NginxFileProtection object.
     *
     * @return NginxFileProtection
     */
    public function createNginxFileProtection()
    {
        return new NginxFileProtection($this->_oWordpress, $this->_oConfig, $this->_oUtil);
    }
}