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
namespace UserAccessManager\Tests\Cache;

use UserAccessManager\Cache\FileSystemCacheProvider;
use UserAccessManager\Tests\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class FileSystemCacheProviderTest
 *
 * @package UserAccessManager\Cache
 * @coversDefaultClass \UserAccessManager\Cache\FileSystemCacheProvider
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
     * @covers ::__construct()
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

        self::assertInstanceOf(FileSystemCacheProvider::class, $fileSystemCacheProvider);
    }

    /**
     * @group  unit
     * @covers ::getId()
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
     * @covers ::init()
     * @covers ::getPath()
     */
    public function testInit()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('path', new Directory());

        $php = $this->getPhp();
        $php->expects($this->once())
            ->method('mkdir')
            ->with('vfs://path/cache/uam/', 0775, true)
            ->will($this->returnCallback(function ($pathname, $mode = 0777, $recursive = false) {
                return mkdir($pathname, $mode, $recursive);
            }));

        $util = $this->getUtil();
        $util->expects($this->once())
            ->method('endsWith')
            ->with('vfs://path/cache/uam', DIRECTORY_SEPARATOR)
            ->will($this->returnValue(false));

        $fileSystemCacheProvider = new FileSystemCacheProvider(
            $php,
            $this->getWordpress(),
            $util,
            $this->getConfigFactory(),
            $this->getConfigParameterFactory()
        );

        $config = $this->getConfig();
        $config->expects($this->once())
            ->method('getParameterValue')
            ->with(FileSystemCacheProvider::CONFIG_PATH)
            ->will($this->returnValue('vfs://path/cache/uam'));

        self::setValue($fileSystemCacheProvider, 'config', $config);
        $fileSystemCacheProvider->init();
        self::assertAttributeEquals('vfs://path/cache/uam/', 'path', $fileSystemCacheProvider);
        self::assertTrue(is_dir('vfs://path/cache/uam/'));
        self::assertTrue(file_exists('vfs://path/cache/uam/.htaccess'));
        self::assertEquals('Deny from all', file_get_contents('vfs://path/cache/uam/.htaccess'));
        self::assertEquals(16839, fileperms('vfs://path/cache/uam'));
    }

    /**
     * @group  unit
     * @covers ::getConfig()
     */
    public function testGetConfig()
    {
        $php = $this->getPhp();

        $php->expects($this->exactly(2))
            ->method('functionExists')
            ->with('igbinary_serialize')
            ->will($this->onConsecutiveCalls(false, true));

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(2))
            ->method('getHomePath')
            ->will($this->returnValue('/homePath/'));

        $config = $this->getConfig();

        $config->expects($this->exactly(2))
            ->method('setDefaultConfigParameters')
            ->with([
                FileSystemCacheProvider::CONFIG_PATH => 'stringConfigParameter',
                FileSystemCacheProvider::CONFIG_METHOD => 'selectionConfigParameter'
            ]);

        $configFactory = $this->getConfigFactory();

        $configFactory->expects($this->exactly(2))
            ->method('createConfig')
            ->with(FileSystemCacheProvider::CONFIG_KEY)
            ->will($this->returnValue($config));

        $configParameterFactory = $this->getConfigParameterFactory();

        $configParameterFactory->expects($this->exactly(2))
            ->method('createStringConfigParameter')
            ->with(FileSystemCacheProvider::CONFIG_PATH, '/homePath/cache/uam')
            ->will($this->returnValue('stringConfigParameter'));


        $configParameterFactory->expects($this->exactly(2))
            ->method('createSelectionConfigParameter')
            ->withConsecutive(
                [
                    FileSystemCacheProvider::CONFIG_METHOD,
                    FileSystemCacheProvider::METHOD_VAR_EXPORT,
                    [
                        FileSystemCacheProvider::METHOD_SERIALIZE,
                        FileSystemCacheProvider::METHOD_JSON,
                        FileSystemCacheProvider::METHOD_VAR_EXPORT
                    ]
                ],
                [
                    FileSystemCacheProvider::CONFIG_METHOD,
                    FileSystemCacheProvider::METHOD_VAR_EXPORT,
                    [
                        FileSystemCacheProvider::METHOD_SERIALIZE,
                        FileSystemCacheProvider::METHOD_JSON,
                        FileSystemCacheProvider::METHOD_VAR_EXPORT,
                        FileSystemCacheProvider::METHOD_IGBINARY
                    ]
                ]
            )
            ->will($this->returnValue('selectionConfigParameter'));

        $fileSystemCacheProvider = new FileSystemCacheProvider(
            $php,
            $wordpress,
            $this->getUtil(),
            $configFactory,
            $configParameterFactory
        );

        self::assertEquals($config, $fileSystemCacheProvider->getConfig());

        self::setValue($fileSystemCacheProvider, 'config', null);
        self::assertEquals($config, $fileSystemCacheProvider->getConfig());

        self::assertEquals($config, $fileSystemCacheProvider->getConfig());
        self::assertAttributeEquals($config, 'config', $fileSystemCacheProvider);
    }

    /**
     * @group  unit
     * @covers ::add()
     * @covers ::getCacheMethod()
     * @covers ::getCacheFile()
     */
    public function testAdd()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('path', new Directory());

        $php = $this->getPhp();

        $php->expects($this->exactly(3))
            ->method('functionExists')
            ->withConsecutive(
                ['igbinary_serialize'],
                ['igbinary_serialize'],
                ['igbinary_unserialize']
            )
            ->will($this->onConsecutiveCalls(false, true, true));

        $php->expects($this->exactly(4))
            ->method('filePutContents')
            ->will($this->returnCallback(function ($filename, $data, $flags = 0, $context = null) {
                return file_put_contents($filename, $data, ($flags !== 0) ? 0 : $flags, $context);
            }));

        $php->expects($this->once())
            ->method('igbinarySerialize')
            ->will($this->returnValue('igbinarySerializeValue'));

        $config = $this->getConfig();

        $config->expects($this->exactly(5))
            ->method('getParameterValue')
            ->will($this->onConsecutiveCalls(
                FileSystemCacheProvider::METHOD_SERIALIZE,
                FileSystemCacheProvider::METHOD_JSON,
                FileSystemCacheProvider::METHOD_VAR_EXPORT,
                FileSystemCacheProvider::METHOD_IGBINARY,
                FileSystemCacheProvider::METHOD_IGBINARY
            ));

        $fileSystemCacheProvider = new FileSystemCacheProvider(
            $php,
            $this->getWordpress(),
            $this->getUtil(),
            $this->getConfigFactory(),
            $this->getConfigParameterFactory()
        );

        self::setValue($fileSystemCacheProvider, 'config', $config);
        self::setValue($fileSystemCacheProvider, 'path', 'vfs://path/');

        $fileSystemCacheProvider->add('serializeCacheKey', 'cacheValue');
        self::assertTrue(file_exists('vfs://path/serializeCacheKey.cache'));
        self::assertEquals('czoxMDoiY2FjaGVWYWx1ZSI7', file_get_contents('vfs://path/serializeCacheKey.cache'));

        $fileSystemCacheProvider->add('jsonCacheKey', ['key' => 'value']);
        self::assertTrue(file_exists('vfs://path/jsonCacheKey.cache'));
        self::assertEquals('{"key":"value"}', file_get_contents('vfs://path/jsonCacheKey.cache'));

        $fileSystemCacheProvider->add('varExportCacheKey', 'cacheValue');
        self::assertTrue(file_exists('vfs://path/varExportCacheKey.php'));
        self::assertEquals(
            "<?php\n\$cachedValue = 'cacheValue';",
            file_get_contents('vfs://path/varExportCacheKey.php')
        );

        $fileSystemCacheProvider->add('igbinaryCacheKey', 'cacheValue');
        $fileSystemCacheProvider->add('igbinaryCacheKey', 'cacheValue');
        self::assertTrue(file_exists('vfs://path/igbinaryCacheKey.cache'));
        self::assertEquals('igbinarySerializeValue', file_get_contents('vfs://path/igbinaryCacheKey.cache'));
    }

    /**
     * @group  unit
     * @covers ::get()
     * @covers ::getCacheMethod()
     * @covers ::getCacheFile()
     */
    public function testGet()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('path', new Directory([
            'serializeCacheKey.cache' => new File('czoxMDoiY2FjaGVWYWx1ZSI7'),
            'jsonCacheKey.cache' => new File('{"key":"value"}'),
            'igbinaryCacheKey.cache' => new File('igbinarySerializeValue')
        ]));

        $php = $this->getPhp();

        $php->expects($this->exactly(6))
            ->method('functionExists')
            ->withConsecutive(
                ['igbinary_serialize'],
                ['igbinary_unserialize'],
                ['igbinary_serialize'],
                ['igbinary_unserialize'],
                ['igbinary_serialize'],
                ['igbinary_unserialize']
            )
            ->will($this->onConsecutiveCalls(true, false, true, true, true, true));

        $php->expects($this->once())
            ->method('igbinaryUnserialize')
            ->will($this->returnValue('igbinarySerializeValue'));

        $config = $this->getConfig();

        $config->expects($this->exactly(7))
            ->method('getParameterValue')
            ->will($this->onConsecutiveCalls(
                FileSystemCacheProvider::METHOD_SERIALIZE,
                FileSystemCacheProvider::METHOD_JSON,
                FileSystemCacheProvider::METHOD_IGBINARY,
                FileSystemCacheProvider::METHOD_IGBINARY,
                FileSystemCacheProvider::METHOD_IGBINARY,
                FileSystemCacheProvider::METHOD_VAR_EXPORT,
                FileSystemCacheProvider::METHOD_VAR_EXPORT
            ));

        $fileSystemCacheProvider = new FileSystemCacheProvider(
            $php,
            $this->getWordpress(),
            $this->getUtil(),
            $this->getConfigFactory(),
            $this->getConfigParameterFactory()
        );

        self::setValue($fileSystemCacheProvider, 'config', $config);
        self::setValue($fileSystemCacheProvider, 'path', 'vfs://path/');

        self::assertEquals('cacheValue', $fileSystemCacheProvider->get('serializeCacheKey'));
        self::assertEquals(['key' => 'value'], $fileSystemCacheProvider->get('jsonCacheKey'));

        self::assertEquals(null, $fileSystemCacheProvider->get('igbinaryCacheKey'));
        self::assertEquals('igbinarySerializeValue', $fileSystemCacheProvider->get('igbinaryCacheKey'));
        self::assertEquals(null, $fileSystemCacheProvider->get('invalid'));

        self::setValue($fileSystemCacheProvider, 'path', '/tmp/');
        file_put_contents('/tmp/varExportCacheKey.php', "<?php\n\$cachedValue = 'cacheValue';");
        self::assertEquals('cacheValue', $fileSystemCacheProvider->get('varExportCacheKey'));

        file_put_contents('/tmp/varExportCacheKey.php', "<?php\n\$otherCachedValue = 'cacheValue';");
        self::assertEquals(null, $fileSystemCacheProvider->get('varExportCacheKey'));
        unlink('/tmp/varExportCacheKey.php');
    }

    /**
     * @group  unit
     * @covers ::invalidate()
     * @covers ::getCacheFile()
     */
    public function testInvalidate()
    {
        $config = $this->getConfig();

        $config->expects($this->exactly(2))
            ->method('getParameterValue')
            ->will($this->onConsecutiveCalls(
                FileSystemCacheProvider::METHOD_SERIALIZE,
                FileSystemCacheProvider::METHOD_VAR_EXPORT
            ));

        $fileSystemCacheProvider = new FileSystemCacheProvider(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getUtil(),
            $this->getConfigFactory(),
            $this->getConfigParameterFactory()
        );

        self::setValue($fileSystemCacheProvider, 'config', $config);
        self::setValue($fileSystemCacheProvider, 'path', '/tmp/');

        file_put_contents('/tmp/serializeCacheKey.cache', 'czoxMDoiY2FjaGVWYWx1ZSI7');
        $fileSystemCacheProvider->invalidate('serializeCacheKey');
        self::assertFalse(file_exists('/tmp/serializeCacheKey.cache'));

        file_put_contents('/tmp/varExportCacheKey.php', "<?php\n\$cachedValue = 'cacheValue';");
        $fileSystemCacheProvider->invalidate('varExportCacheKey');
        self::assertFalse(file_exists('/tmp/varExportCacheKey.php'));
    }
}
