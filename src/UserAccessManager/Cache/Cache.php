<?php
/**
 * Cache.php
 *
 * The Cache class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Cache;

/**
 * Class Cache
 *
 * @package UserAccessManager\Cache
 */
class Cache
{
    /**
     * @var array
     */
    protected $_aCache = [];

    /**
     * Returns a generated cache key.
     *
     * @return string
     */
    public function generateCacheKey()
    {
        $aArguments = func_get_args();

        return implode('|', $aArguments);
    }

    /**
     * Adds the variable to the cache.
     *
     * @param string $sKey   The cache key
     * @param mixed  $mValue The value.
     */
    public function addToCache($sKey, $mValue)
    {
        $this->_aCache[$sKey] = $mValue;
    }

    /**
     * Returns a value from the cache by the given key.
     *
     * @param string $sKey
     *
     * @return mixed
     */
    public function getFromCache($sKey)
    {
        if (isset($this->_aCache[$sKey])) {
            return $this->_aCache[$sKey];
        }

        return null;
    }

    /**
     * Flushes the cache.
     */
    public function flushCache()
    {
        $this->_aCache = [];
    }
}