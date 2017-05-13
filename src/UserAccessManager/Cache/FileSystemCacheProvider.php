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

use UserAccessManager\UserAccessManager;

/**
 * Class FileSystemCacheProvider
 *
 * @package UserAccessManager\Cache
 */
class FileSystemCacheProvider implements CacheProviderInterface
{
    const METHOD_SERIALIZE = 'serialize';
    const METHOD_IGBINARY = 'igbinary';
    const METHOD_JSON = 'json';
    const METHOD_VAR_EXPORT = 'var_export';

    /**
     * @var string
     */
    private $method = self::METHOD_VAR_EXPORT;

    /**
     * @var UserAccessManager
     */
    private $userAccessManager;

    /**
     * @var string
     */
    private $path = '/var/www/app/cache/uam';

    /**
     * FileSystemCacheProvider constructor.
     *
     * @param UserAccessManager $userAccessManager
     */
    public function __construct(UserAccessManager $userAccessManager)
    {
        $this->userAccessManager = $userAccessManager;

        if ($this->userAccessManager->getUtil()->endsWith($this->path, DIRECTORY_SEPARATOR) === false) {
            $this->path .= DIRECTORY_SEPARATOR;
        }

        if (is_dir($this->path) === false) {
            mkdir($this->path, 0777, true);
        }

        $htaccessFile = $this->path.'.htaccess';

        if (file_exists($htaccessFile) === false) {
            file_put_contents($htaccessFile, 'Deny from all');
        }
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function add($key, $value)
    {
        $cacheFile = $this->path.$key;
        $cacheFile .= ($this->method === self::METHOD_VAR_EXPORT) ? '.php' : '.cache';

        if ($this->method === self::METHOD_SERIALIZE) {
            file_put_contents($cacheFile, base64_encode(serialize($value)), LOCK_EX);
        } elseif ($this->method === self::METHOD_IGBINARY
            && $this->userAccessManager->getPhp()->functionExists('igbinary_serialize')
        ) {
            /** @noinspection PhpUndefinedFunctionInspection */
            file_put_contents($cacheFile, igbinary_serialize($value), LOCK_EX);
        } elseif ($this->method === self::METHOD_JSON) {
            file_put_contents($cacheFile, json_encode($value), LOCK_EX);
        } elseif ($this->method === self::METHOD_VAR_EXPORT) {
            file_put_contents($cacheFile, "<?php\n\$cachedValue = ".var_export($value, true).';', LOCK_EX);
        }
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $cacheFile = $this->path.$key;
        $cacheFile .= ($this->method === self::METHOD_VAR_EXPORT) ? '.php' : '.cache';

        if ((file_exists($cacheFile) === true)) {
            if ($this->method === self::METHOD_SERIALIZE) {
                return base64_decode(unserialize(file_get_contents($cacheFile), true));
            } elseif ($this->method === self::METHOD_IGBINARY
                && $this->userAccessManager->getPhp()->functionExists('igbinary_unserialize')
            ) {
                /** @noinspection PhpUndefinedFunctionInspection */
                return igbinary_unserialize(file_get_contents($cacheFile));
            } elseif ($this->method === self::METHOD_JSON) {
                return json_decode(file_get_contents($cacheFile), true);
            } elseif ($this->method === self::METHOD_VAR_EXPORT) {
                /** @noinspection PhpIncludeInspection */
                include($cacheFile);
                return isset($cachedValue) ? $cachedValue : null;
            }
        }

        return null;
    }

    /**
     * @param string $key
     */
    public function invalidate($key)
    {
        $cacheFile = $this->path.$key;
        $cacheFile .= ($this->method === self::METHOD_VAR_EXPORT) ? '.php' : '.cache';

        if ((file_exists($cacheFile) === true)) {
            unlink($cacheFile);
        }
    }
}
