<?php
/**
 * FileSystemCacheProvider.php
 *
 * The FileSystemCacheProvider interface file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Cache;

use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigFactory;
use UserAccessManager\Config\ConfigParameterFactory;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class FileSystemCacheProvider
 *
 * @package UserAccessManager\Cache
 */
class FileSystemCacheProvider implements CacheProviderInterface
{
    const ID = 'FileSystemCacheProvider';
    const CONFIG_KEY = 'uam_file_system_cache_provider';
    const CONFIG_PATH = 'fs_cache_path';
    const CONFIG_METHOD = 'fs_cache_method';
    const METHOD_SERIALIZE = 'serialize';
    const METHOD_IGBINARY = 'igbinary';
    const METHOD_JSON = 'json';
    const METHOD_VAR_EXPORT = 'var_export';

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
     * @var null|Config
     */
    private $config = null;

    /**
     * @var string
     */
    private $path;

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

        $this->path = $this->getConfig()->getParameterValue(self::CONFIG_PATH);

        if ($this->util->endsWith($this->path, DIRECTORY_SEPARATOR) === false) {
            $this->path .= DIRECTORY_SEPARATOR;
        }
    }

    /**
     * Returns the id.
     *
     * @return string
     */
    public function getId()
    {
        return self::ID;
    }

    /**
     * Initialise the caching path.
     */
    public function init()
    {
        if (is_dir($this->path) === false) {
            mkdir($this->path, 0777, true);
        }

        $htaccessFile = $this->path.'.htaccess';

        if (file_exists($htaccessFile) === false) {
            file_put_contents($htaccessFile, 'Deny from all');
        }
    }

    /**
     * Returns the cache config.
     *
     * @return Config
     */
    public function getConfig()
    {
        if ($this->config === null) {
            $this->config = $this->configFactory->createConfig(self::CONFIG_KEY);

            $configParameters = [
                self::CONFIG_PATH => $this->configParameterFactory->createStringConfigParameter(
                    self::CONFIG_PATH,
                    $this->wordpress->getHomePath().'cache/uam'
                ),
                self::CONFIG_METHOD => $this->configParameterFactory->createSelectionConfigParameter(
                    self::CONFIG_METHOD,
                    self::METHOD_VAR_EXPORT,
                    [
                        self::METHOD_SERIALIZE,
                        self::METHOD_IGBINARY ,
                        self::METHOD_JSON,
                        self::METHOD_VAR_EXPORT
                    ]
                )
            ];
            $this->config->setDefaultConfigParameters($configParameters);
        }

        return $this->config;
    }

    /**
     * Adds a value to the cache.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function add($key, $value)
    {
        $method = $this->getConfig()->getParameterValue(self::CONFIG_METHOD);
        $cacheFile = $this->path.$key;
        $cacheFile .= ($method === self::METHOD_VAR_EXPORT) ? '.php' : '.cache';

        if ($method === self::METHOD_SERIALIZE) {
            file_put_contents($cacheFile, base64_encode(serialize($value)), LOCK_EX);
        } elseif ($method === self::METHOD_IGBINARY
            && $this->php->functionExists('igbinary_serialize')
        ) {
            /** @noinspection PhpUndefinedFunctionInspection */
            file_put_contents($cacheFile, igbinary_serialize($value), LOCK_EX);
        } elseif ($method === self::METHOD_JSON) {
            file_put_contents($cacheFile, json_encode($value), LOCK_EX);
        } elseif ($method === self::METHOD_VAR_EXPORT) {
            file_put_contents($cacheFile, "<?php\n\$cachedValue = ".var_export($value, true).';', LOCK_EX);
        }
    }

    /**
     * Returns a value from the cache.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $method = $this->getConfig()->getParameterValue(self::CONFIG_METHOD);
        $cacheFile = $this->path.$key;
        $cacheFile .= ($method === self::METHOD_VAR_EXPORT) ? '.php' : '.cache';

        if ((file_exists($cacheFile) === true)) {
            if ($method === self::METHOD_SERIALIZE) {
                return base64_decode(unserialize(file_get_contents($cacheFile), true));
            } elseif ($method === self::METHOD_IGBINARY
                && $this->php->functionExists('igbinary_unserialize')
            ) {
                /** @noinspection PhpUndefinedFunctionInspection */
                return igbinary_unserialize(file_get_contents($cacheFile));
            } elseif ($method === self::METHOD_JSON) {
                return json_decode(file_get_contents($cacheFile), true);
            } elseif ($method === self::METHOD_VAR_EXPORT) {
                /** @noinspection PhpIncludeInspection */
                include($cacheFile);
                return isset($cachedValue) ? $cachedValue : null;
            }
        }

        return null;
    }

    /**
     * Invalidates the cache.
     *
     * @param string $key
     */
    public function invalidate($key)
    {
        $method = $this->getConfig()->getParameterValue(self::CONFIG_METHOD);
        $cacheFile = $this->path.$key;
        $cacheFile .= ($method === self::METHOD_VAR_EXPORT) ? '.php' : '.cache';

        if ((file_exists($cacheFile) === true)) {
            unlink($cacheFile);
        }
    }
}
