<?php
/**
 * FileSystemCacheProviderTest.php
 *
 * The FileSystemCacheProviderTest unit test class file.
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

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class FileSystemCacheProviderTest
 *
 * @package UserAccessManager\Cache
 */
class FileSystemCacheProviderTest extends UserAccessManagerTestCase
{
    /**
     * @group unit
     * @covers \UserAccessManager\Cache\FileSystemCacheProvider::__construct()
     *
     * @return FileSystemCacheProvider
     */
    public function testCanCreateInstance()
    {
        $util = $this->getUtil();
        $util->expects($this->once())
            ->method('endsWith')
            ->with('/var/www/app/cache/uam', DIRECTORY_SEPARATOR)
            ->will($this->returnValue(false));

        $userAccessManger = $this->getUserAccessManager();
        $userAccessManger->expects($this->once())
            ->method('getUtil')
            ->will($this->returnValue($util));

        $fileSystemCacheProvider = new FileSystemCacheProvider($userAccessManger);
        self::assertInstanceOf('\UserAccessManager\Cache\FileSystemCacheProvider', $fileSystemCacheProvider);
        return $fileSystemCacheProvider;
    }
}
