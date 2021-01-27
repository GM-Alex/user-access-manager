<?php
/**
 * CacheProviderFactory.php
 *
 * The CacheProviderFactory class file.
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

namespace UserAccessManager\Cache;

use UserAccessManager\Config\ConfigFactory;
use UserAccessManager\Config\ConfigParameterFactory;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class CacheProviderFactory
 *
 * @package UserAccessManager\Cache
 */
class CacheProviderFactory
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
     * @var Util
     */
    private $util;

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var ConfigParameterFactory
     */
    private $configParameterFactory;

    /**
     * CacheProviderFactory constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param Util $util
     * @param ConfigFactory $configFactory
     * @param ConfigParameterFactory $configParameterFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Util $util,
        ConfigFactory $configFactory,
        ConfigParameterFactory $configParameterFactory
    ) {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->util = $util;
        $this->configFactory = $configFactory;
        $this->configParameterFactory = $configParameterFactory;
    }

    /**
     * Creates a FileSystemCacheProvider object.
     * @return FileSystemCacheProvider
     */
    public function createFileSystemCacheProvider(): FileSystemCacheProvider
    {
        return new FileSystemCacheProvider(
            $this->php,
            $this->wordpress,
            $this->util,
            $this->configFactory,
            $this->configParameterFactory
        );
    }
}
