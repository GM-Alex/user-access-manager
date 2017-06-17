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
     */
    public function testCanCreateInstance()
    {
        $fileSystemCacheProvider = new FileSystemCacheProvider(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getUtil(),
            $this->getConfigFactory(),
            $this->getConfigParameterFactory()
        );

        self::assertInstanceOf('\UserAccessManager\Cache\FileSystemCacheProvider', $fileSystemCacheProvider);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Cache\FileSystemCacheProvider::getId()
     */
    public function testGetId()
    {
        $fileSystemCacheProvider = new FileSystemCacheProvider(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getUtil(),
            $this->getConfigFactory(),
            $this->getConfigParameterFactory()
        );

        self::assertEquals(FileSystemCacheProvider::ID, $fileSystemCacheProvider->getId());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Cache\FileSystemCacheProvider::init()
     * @covers \UserAccessManager\Cache\FileSystemCacheProvider::getPath()
     */
    public function testInit()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('path', new Directory());

        $util = $this->getUtil();
        $util->expects($this->once())
            ->method('endsWith')
            ->with('vfs://path/cache', DIRECTORY_SEPARATOR)
            ->will($this->returnValue(false));

        $fileSystemCacheProvider = new FileSystemCacheProvider(
            $this->getPhp(),
            $this->getWordpress(),
            $util,
            $this->getConfigFactory(),
            $this->getConfigParameterFactory()
        );

        $config = $this->getConfig();
        $config->expects($this->once())
            ->method('getParameterValue')
            ->with(FileSystemCacheProvider::CONFIG_PATH)
            ->will($this->returnValue('vfs://path/cache'));

        self::setValue($fileSystemCacheProvider, 'config', $config);
        $fileSystemCacheProvider->init();
        self::assertAttributeEquals('vfs://path/cache/', 'path', $fileSystemCacheProvider);
        self::assertTrue(is_dir('vfs://path/cache/'));
        self::assertTrue(file_exists('vfs://path/cache/.htaccess'));
        self::assertEquals('Deny from all', file_get_contents('vfs://path/cache/.htaccess'));
    }
}
