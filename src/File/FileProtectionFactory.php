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

declare(strict_types=1);

namespace UserAccessManager\File;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
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
     * @var WordpressConfig
     */
    private $wordpressConfig;

    /**
     * @var MainConfig
     */
    private $mainConfig;

    /**
     * @var Util
     */
    private $util;

    /**
     * FileProtectionFactory constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param WordpressConfig $wordpressConfig
     * @param MainConfig $mainConfig
     * @param Util $util
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        Util $util
    ) {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->wordpressConfig = $wordpressConfig;
        $this->mainConfig = $mainConfig;
        $this->util = $util;
    }

    /**
     * Returns a new ApacheFileProtection object.
     * @return ApacheFileProtection
     */
    public function createApacheFileProtection(): ApacheFileProtection
    {
        return new ApacheFileProtection(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->util
        );
    }

    /**
     * Returns a new NginxFileProtection object.
     * @return NginxFileProtection
     */
    public function createNginxFileProtection(): NginxFileProtection
    {
        return new NginxFileProtection(
            $this->php,
            $this->wordpress,
            $this->wordpressConfig,
            $this->mainConfig,
            $this->util
        );
    }
}
