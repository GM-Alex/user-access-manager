<?php
/**
 * CacheProviderInterface.php
 *
 * The CacheProviderInterface interface file.
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
 * Interface CacheProviderInterface
 *
 * @package UserAccessManager\Cache
 */
interface CacheProviderInterface
{
    /**
     * CacheProviderInterface constructor.
     *
     * @param UserAccessManager $userAccessManager
     */
    public function __construct(UserAccessManager $userAccessManager);

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function add($key, $value);

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key);

    /**
     * @param string $key
     */
    public function invalidate($key);
}
