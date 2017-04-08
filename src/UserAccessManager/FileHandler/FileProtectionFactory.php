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
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class FileProtectionFactory
 *
 * @package UserAccessManager\FileHandler
 */
class FileProtectionFactory
{
    /**
     * @var Php
     */
    protected $oPhp;

    /**
     * @var Wordpress
     */
    protected $oWordpress;

    /**
     * @var Config
     */
    protected $oConfig;

    /**
     * @var FileHandler
     */
    protected $oFileHandler;

    /**
     * @var Util
     */
    protected $oUtil;

    /**
     * FileProtectionFactory constructor.
     *
     * @param Php       $oPhp
     * @param Wordpress $oWordpress
     * @param Config    $oConfig
     * @param Util      $oUtil
     */
    public function __construct(Php $oPhp, Wordpress $oWordpress, Config $oConfig, Util $oUtil)
    {
        $this->oPhp = $oPhp;
        $this->oWordpress = $oWordpress;
        $this->oConfig = $oConfig;
        $this->oUtil = $oUtil;
    }

    /**
     * Returns a new ApacheFileProtection object.
     *
     * @return ApacheFileProtection
     */
    public function createApacheFileProtection()
    {
        return new ApacheFileProtection($this->oPhp, $this->oWordpress, $this->oConfig, $this->oUtil);
    }

    /**
     * Returns a new NginxFileProtection object.
     *
     * @return NginxFileProtection
     */
    public function createNginxFileProtection()
    {
        return new NginxFileProtection($this->oPhp, $this->oWordpress, $this->oConfig, $this->oUtil);
    }
}
