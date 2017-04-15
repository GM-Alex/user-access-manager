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
 * @version   SVN: $id$
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
    private $php;

    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Util
     */
    private $util;

    /**
     * FileProtectionFactory constructor.
     *
     * @param Php       $php
     * @param Wordpress $wordpress
     * @param Config    $config
     * @param Util      $util
     */
    public function __construct(Php $php, Wordpress $wordpress, Config $config, Util $util)
    {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->config = $config;
        $this->util = $util;
    }

    /**
     * Returns a new ApacheFileProtection object.
     *
     * @return ApacheFileProtection
     */
    public function createApacheFileProtection()
    {
        return new ApacheFileProtection($this->php, $this->wordpress, $this->config, $this->util);
    }

    /**
     * Returns a new NginxFileProtection object.
     *
     * @return NginxFileProtection
     */
    public function createNginxFileProtection()
    {
        return new NginxFileProtection($this->php, $this->wordpress, $this->config, $this->util);
    }
}
