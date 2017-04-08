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
    protected $Php;

    /**
     * @var Wordpress
     */
    protected $Wordpress;

    /**
     * @var Config
     */
    protected $Config;

    /**
     * @var FileHandler
     */
    protected $FileHandler;

    /**
     * @var Util
     */
    protected $Util;

    /**
     * FileProtectionFactory constructor.
     *
     * @param Php       $Php
     * @param Wordpress $Wordpress
     * @param Config    $Config
     * @param Util      $Util
     */
    public function __construct(Php $Php, Wordpress $Wordpress, Config $Config, Util $Util)
    {
        $this->Php = $Php;
        $this->Wordpress = $Wordpress;
        $this->Config = $Config;
        $this->Util = $Util;
    }

    /**
     * Returns a new ApacheFileProtection object.
     *
     * @return ApacheFileProtection
     */
    public function createApacheFileProtection()
    {
        return new ApacheFileProtection($this->Php, $this->Wordpress, $this->Config, $this->Util);
    }

    /**
     * Returns a new NginxFileProtection object.
     *
     * @return NginxFileProtection
     */
    public function createNginxFileProtection()
    {
        return new NginxFileProtection($this->Php, $this->Wordpress, $this->Config, $this->Util);
    }
}
