<?php
/**
 * CacheTest.php
 *
 * The CacheTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Cache;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Cache\FileSystemCacheProvider;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class CacheTest
 * @package UserAccessManager\Tests\Unit\Cache
 * @coversDefaultClass \UserAccessManager\Cache\Cache
 */
class CacheTest extends UserAccessManagerTestCase
{
    /**
     * @group unit
     * @return Cache
     */
    public function testCanCreateInstance(): Cache
    {
        $cache = new Cache(
            $this->getWordpress(),
            $this->getCacheProviderFactory()
        );
        self::assertInstanceOf(Cache::class, $cache);
        return $cache;
    }

    /**
     * @group unit
     * @covers ::setActiveCacheProvider()
     * @covers ::getRegisteredCacheProviders()
     * @covers ::getCacheProvider()
     */
    public function testSetActiveCacheProvider()
    {
        $fileSystemCacheProvider = $this->createMock(FileSystemCacheProvider::class);

        $fileSystemCacheProvider->expects($this->once())
            ->method('init');
        $fileSystemCacheProvider->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue('cacheProvider'));

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('applyFilters')
            ->with('uam_registered_cache_handlers', ['cacheProvider' => $fileSystemCacheProvider])
            ->will($this->returnValue(['cacheProvider' => $fileSystemCacheProvider]));

        $cacheProviderFactory = $this->getCacheProviderFactory();
        $cacheProviderFactory->expects($this->exactly(2))
            ->method('createFileSystemCacheProvider')
            ->will($this->returnValue($fileSystemCacheProvider));

        $cache = new Cache(
            $wordpress,
            $cacheProviderFactory
        );

        self::assertEmpty($cache->getCacheProvider());

        $cache->setActiveCacheProvider('invalid');
        self::assertEmpty($cache->getCacheProvider());

        $cache->setActiveCacheProvider('cacheProvider');
        self::assertEquals($fileSystemCacheProvider, $cache->getCacheProvider());
    }

    /**
     * @group unit
     * @depends testCanCreateInstance
     * @covers ::generateCacheKey()
     * @param Cache $cache
     */
    public function testGenerateCacheKey(Cache $cache)
    {
        $key = $cache->generateCacheKey(
            'preFix',
            'cacheKey',
            'postFix'
        );
        self::assertEquals('preFix|cacheKey|postFix', $key);
    }

    /**
     * @return MockObject|FileSystemCacheProvider
     */
    protected function getFileSystemCacheProvider()
    {
        $fileSystemCacheProvider = parent::getFileSystemCacheProvider();
        $fileSystemCacheProvider->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('cacheProvider'));
        $fileSystemCacheProvider->expects($this->any())
            ->method('get')
            ->with('onlyInCacheProvider')
            ->will($this->returnValue('cacheProviderValue'));

        return $fileSystemCacheProvider;
    }

    /**
     * @group unit
     * @depends testCanCreateInstance
     * @covers ::add()
     * @param Cache $cache
     * @return Cache
     */
    public function testAdd(Cache $cache): Cache
    {
        $cache->add('stringCacheKey', 'testValue');
        $fileSystemCacheProvider = $this->getFileSystemCacheProvider();
        $fileSystemCacheProvider->expects($this->once())
            ->method('add')
            ->with('arrayCacheKey', ['testString', 'testString2']);
        $fileSystemCacheProvider->expects($this->any())
            ->method('invalidate')
            ->with('arrayCacheKey');

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->any())
            ->method('applyFilters')
            ->with('uam_registered_cache_handlers', ['cacheProvider' => $fileSystemCacheProvider])
            ->will($this->returnValue(['cacheProvider' => $fileSystemCacheProvider]));

        $cacheProviderFactory = $this->getCacheProviderFactory();
        $cacheProviderFactory->expects($this->any())
            ->method('createFileSystemCacheProvider')
            ->will($this->returnValue($fileSystemCacheProvider));

        $cache = new Cache(
            $wordpress,
            $cacheProviderFactory
        );

        $cache->add('stringCacheKey', 'testValue');
        $cache->setActiveCacheProvider('cacheProvider');
        $cache->add('arrayCacheKey', ['testString', 'testString2']);

        self::assertEquals('testValue', $cache->get('stringCacheKey'));
        self::assertEquals(['testString', 'testString2'], $cache->get('arrayCacheKey'));

        return $cache;
    }

    /**
     * @group unit
     * @depends testAdd
     * @covers ::get()
     * @param Cache $cache
     * @return Cache
     * @throws ReflectionException
     */
    public function testGet(Cache $cache): Cache
    {
        $cache->setActiveCacheProvider('cacheProvider');

        self::assertEquals('testValue', $cache->get('stringCacheKey'));
        self::assertEquals(
            ['testString', 'testString2'],
            $cache->get('arrayCacheKey')
        );
        self::assertEquals(
            'cacheProviderValue',
            $cache->get('onlyInCacheProvider')
        );

        self::setValue($cache, 'cacheProvider', null);

        self::assertEquals(
            null,
            $cache->get('notSet')
        );

        return $cache;
    }

    /**
     * @group unit
     * @covers ::invalidate()
     * @throws ReflectionException
     */
    public function testInvalidate()
    {
        $fileSystemCacheProvider = $this->getFileSystemCacheProvider();
        $fileSystemCacheProvider->expects($this->exactly(1))
            ->method('invalidate')
            ->with('arrayCacheKey');

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->any())
            ->method('applyFilters')
            ->with('uam_registered_cache_handlers', ['cacheProvider' => $fileSystemCacheProvider])
            ->will($this->returnValue(['cacheProvider' => $fileSystemCacheProvider]));

        $cacheProviderFactory = $this->getCacheProviderFactory();
        $cacheProviderFactory->expects($this->any())
            ->method('createFileSystemCacheProvider')
            ->will($this->returnValue($fileSystemCacheProvider));

        $cache = new Cache(
            $wordpress,
            $cacheProviderFactory
        );

        $cache->setActiveCacheProvider('cacheProvider');

        $cache->add('stringCacheKey', 'testValue');
        $cache->add('arrayCacheKey', ['testString', 'testString2']);

        $cache->invalidate('arrayCacheKey');
        self::assertEquals('testValue', $cache->get('stringCacheKey'));
        self::assertEquals('cacheProviderValue', $cache->get('onlyInCacheProvider'));

        self::setValue($cache, 'cacheProvider', null);
        $cache->invalidate('notSet');
    }

    /**
     * @group unit
     * @depends testCanCreateInstance
     * @covers ::addToRuntimeCache()
     * @param Cache $cache
     * @return Cache
     */
    public function testAddToCache(Cache $cache): Cache
    {
        $cache->addToRuntimeCache('stringCacheKey', 'testValue');
        $cache->addToRuntimeCache('arrayCacheKey', ['testString', 'testString2']);

        self::assertEquals('testValue', $cache->getFromRuntimeCache('stringCacheKey'));
        self::assertEquals(['testString', 'testString2'], $cache->getFromRuntimeCache('arrayCacheKey'));

        return $cache;
    }

    /**
     * @group unit
     * @depends testAddToCache
     * @covers ::getFromRuntimeCache()
     * @param Cache $cache
     * @return Cache
     */
    public function testGetFromCache(Cache $cache): Cache
    {
        self::assertEquals('testValue', $cache->getFromRuntimeCache('stringCacheKey'));
        self::assertEquals(
            ['testString', 'testString2'],
            $cache->getFromRuntimeCache('arrayCacheKey')
        );
        self::assertEquals(
            null,
            $cache->getFromRuntimeCache('notSet')
        );

        return $cache;
    }

    /**
     * @group unit
     * @depends testGetFromCache
     * @covers ::flushCache()
     * @param Cache $cache
     * @throws Exception
     */
    public function testFlushCache(Cache $cache)
    {
        $cache->flushCache();
        self::assertEmpty($cache->get(random_bytes(10)));
    }
}
