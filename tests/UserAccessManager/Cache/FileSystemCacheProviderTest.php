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
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class FileSystemCacheProviderTest
 *
 * @package UserAccessManager\Cache
 */
class FileSystemCacheProviderTest extends UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private $root;

    /**
     * Setup virtual file system.
     */
    public function setUp()
    {
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();
    }

    /**
     * Tear down virtual file system.
     */
    public function tearDown()
    {
        $this->root->unmount();
    }

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

        $fileSystemCacheProvider = new FileSystemCacheProvider(
            $this->getPhp(),
            $this->getWordpress(),
            $util,
            $this->getConfigFactory(),
            $this->getConfigParameterFactory()
        );
        self::assertInstanceOf('\UserAccessManager\Cache\FileSystemCacheProvider', $fileSystemCacheProvider);
        return $fileSystemCacheProvider;
    }
}
