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

use UserAccessManager\Cache\Cache;
use UserAccessManager\Cache\FileSystemCacheProvider;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class CacheTest
 *
 * @package UserAccessManager\Tests\Unit\Cache
 * @coversDefaultClass \UserAccessManager\Cache\Cache
 */
class CacheTest extends UserAccessManagerTestCase
{
    /**
     * @group unit
     *
     * @return Cache
     */
    public function testCanCreateInstance()
    {
        $cache = new Cache(
            $this->getWordpress(),
            $this->getCacheProviderFactory()
        );
        self::assertInstanceOf(Cache::class, $cache);
        return $cache;
    }

    /**
     * @group  unit
     * @covers ::setActiveCacheProvider()
     * @covers ::getRegisteredCacheProviders()
     */
    public function testSetActiveCacheProvider()
    {
        $fileSystemCacheProvider = $this->createMock(FileSystemCacheProvider::class);
        $fileSystemCacheProvider->expects($this->exactly(2))
            ->method('getId')
            ->will($this->returnValue('cacheProvider'));

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('applyFilters')
            ->with('uam_registered_cache_handlers', ['cacheProvider' => $fileSystemCacheProvider])
            ->will($this->returnValue(['cacheProvider' => $fileSystemCacheProvider]));

        $cacheProviderFactory =$this->getCacheProviderFactory();
        $cacheProviderFactory->expects($this->exactly(2))
            ->method('createFileSystemCacheProvider')
            ->will($this->returnValue($fileSystemCacheProvider));

        $cache = new Cache(
            $wordpress,
            $cacheProviderFactory
        );

        self::assertAttributeEmpty('cacheProvider', $cache);

        $cache->setActiveCacheProvider('invalid');
        self::assertAttributeEmpty('cacheProvider', $cache);

        $cache->setActiveCacheProvider('cacheProvider');
        self::assertAttributeEquals($fileSystemCacheProvider, 'cacheProvider', $cache);
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::generateCacheKey()
     *
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
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::add()
     *
     * @param Cache $cache
     *
     * @return Cache
     */
    public function testAdd(Cache $cache)
    {
        $cache->add('stringCacheKey', 'testValue');


        $fileSystemCacheProvider = $this->createMock(FileSystemCacheProvider::class);
        $fileSystemCacheProvider->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('cacheProvider'));
        $fileSystemCacheProvider->expects($this->once())
            ->method('add')
            ->with('arrayCacheKey', ['testString', 'testString2']);
        $fileSystemCacheProvider->expects($this->any())
            ->method('get')
            ->with('onlyInCacheProvider')
            ->will($this->returnValue('cacheProviderValue'));
        $fileSystemCacheProvider->expects($this->any())
            ->method('invalidate')
            ->with('arrayCacheKey');

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->any())
            ->method('applyFilters')
            ->with('uam_registered_cache_handlers', ['cacheProvider' => $fileSystemCacheProvider])
            ->will($this->returnValue(['cacheProvider' => $fileSystemCacheProvider]));

        $cacheProviderFactory =$this->getCacheProviderFactory();
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

        self::assertAttributeEquals(
            [
                'stringCacheKey' => 'testValue',
                'arrayCacheKey' => ['testString', 'testString2']
            ],
            'cache',
            $cache
        );

        return $cache;
    }

    /**
     * @group   unit
     * @depends testAdd
     * @covers  ::get()
     *
     * @param Cache $cache
     *
     * @return Cache
     */
    public function testGet($cache)
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
     * @group   unit
     * @depends testAdd
     * @covers  ::invalidate()
     *
     * @param Cache $cache
     */
    public function testInvalidate(Cache $cache)
    {
        $cache->setActiveCacheProvider('cacheProvider');

        $cache->invalidate('arrayCacheKey');
        self::assertAttributeEquals(
            [
                'stringCacheKey' => 'testValue',
                'onlyInCacheProvider' => 'cacheProviderValue',
                'notSet' => null
            ],
            'cache',
            $cache
        );

        self::setValue($cache, 'cacheProvider', null);
        $cache->invalidate('notSet');
        self::assertAttributeEquals(
            ['stringCacheKey' => 'testValue', 'onlyInCacheProvider' => 'cacheProviderValue'],
            'cache',
            $cache
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::addToRuntimeCache()
     *
     * @param Cache $cache
     *
     * @return Cache
     */
    public function testAddToCache(Cache $cache)
    {
        $cache->addToRuntimeCache('stringCacheKey', 'testValue');
        $cache->addToRuntimeCache('arrayCacheKey', ['testString', 'testString2']);

        self::assertAttributeEquals(
            [
                'stringCacheKey' => 'testValue',
                'arrayCacheKey' => ['testString', 'testString2']
            ],
            'runtimeCache',
            $cache
        );

        return $cache;
    }

    /**
     * @group   unit
     * @depends testAddToCache
     * @covers  ::getFromRuntimeCache()
     *
     * @param Cache $cache
     *
     * @return Cache
     */
    public function testGetFromCache($cache)
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
     * @group   unit
     * @depends testAddToCache
     * @covers  ::flushCache()
     *
     * @param Cache $cache
     */
    public function testFlushCache($cache)
    {
        //TODO
        self::assertAttributeEquals(
            [
                'stringCacheKey' => 'testValue',
                'arrayCacheKey' => ['testString', 'testString2']
            ],
            'runtimeCache',
            $cache
        );

        $cache->flushCache();

        self::assertAttributeEquals([], 'cache', $cache);
        self::assertAttributeEquals([], 'runtimeCache', $cache);
    }
}
