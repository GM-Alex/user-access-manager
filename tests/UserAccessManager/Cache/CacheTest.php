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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Cache;

/**
 * Class CacheTest
 *
 * @package UserAccessManager\Cache
 */
class CacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group unit
     *
     * @return Cache
     */
    public function testCanCreateInstance()
    {
        $Cache = new Cache();
        self::assertInstanceOf('\UserAccessManager\Cache\Cache', $Cache);
        return $Cache;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Cache\Cache::generateCacheKey()
     *
     * @param Cache $Cache
     */
    public function testGenerateCacheKey(Cache $Cache)
    {
        $sKey = $Cache->generateCacheKey(
            'preFix',
            'cacheKey',
            'postFix'
        );
        self::assertEquals('preFix|cacheKey|postFix', $sKey);
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Cache\Cache::addToCache()
     *
     * @param Cache $Cache
     *
     * @return Cache
     */
    public function testAddToCache(Cache $Cache)
    {
        $Cache->addToCache('stringCacheKey', 'testValue');
        $Cache->addToCache('arrayCacheKey', ['testString', 'testString2']);

        self::assertAttributeEquals(
            [
                'stringCacheKey' => 'testValue',
                'arrayCacheKey' => ['testString', 'testString2']
            ],
            'aCache',
            $Cache
        );

        return $Cache;
    }

    /**
     * @group   unit
     * @depends testAddToCache
     * @covers  \UserAccessManager\Cache\Cache::getFromCache()
     *
     * @param Cache $Cache
     *
     * @return Cache
     */
    public function testGetFromCache($Cache)
    {
        self::assertEquals('testValue', $Cache->getFromCache('stringCacheKey'));
        self::assertEquals(
            ['testString', 'testString2'],
            $Cache->getFromCache('arrayCacheKey')
        );
        self::assertEquals(
            null,
            $Cache->getFromCache('notSet')
        );

        return $Cache;
    }

    /**
     * @group   unit
     * @depends testAddToCache
     * @covers  \UserAccessManager\Cache\Cache::flushCache()
     *
     * @param Cache $Cache
     */
    public function testFlushCache($Cache)
    {
        self::assertAttributeEquals(
            [
                'stringCacheKey' => 'testValue',
                'arrayCacheKey' => ['testString', 'testString2']
            ],
            'aCache',
            $Cache
        );

        $Cache->flushCache();

        self::assertAttributeEquals([], 'aCache', $Cache);
    }
}
