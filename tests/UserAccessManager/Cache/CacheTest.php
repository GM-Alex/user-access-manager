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
        $oCache = new Cache();
        self::assertInstanceOf('\UserAccessManager\Cache\Cache', $oCache);
        return $oCache;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Cache\Cache::generateCacheKey()
     * @depends testCanCreateInstance
     *
     * @param Cache $oCache
     */
    public function testGenerateCacheKey(Cache $oCache)
    {
        $sKey = $oCache->generateCacheKey(
            'preFix',
            'cacheKey',
            'postFix'
        );
        self::assertEquals('preFix|cacheKey|postFix', $sKey);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Cache\Cache::addToCache()
     * @depends testCanCreateInstance
     *
     * @param Cache $oCache
     *
     * @return Cache
     */
    public function testAddToCache(Cache $oCache)
    {
        $oCache->addToCache('stringCacheKey', 'testValue');
        $oCache->addToCache('arrayCacheKey', ['testString', 'testString2']);

        self::assertAttributeEquals(
            [
                'stringCacheKey' => 'testValue',
                'arrayCacheKey' => ['testString', 'testString2']
            ],
            '_aCache',
            $oCache
        );

        return $oCache;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Cache\Cache::getFromCache()
     * @depends testAddToCache
     *
     * @param Cache $oCache
     *
     * @return Cache
     */
    public function testGetFromCache($oCache)
    {
        self::assertEquals('testValue', $oCache->getFromCache('stringCacheKey'));
        self::assertEquals(
            ['testString', 'testString2'],
            $oCache->getFromCache('arrayCacheKey')
        );
        self::assertEquals(
            null,
            $oCache->getFromCache('notSet')
        );

        return $oCache;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Cache\Cache::flushCache()
     * @depends testAddToCache
     *
     * @param Cache $oCache
     */
    public function testFlushCache($oCache)
    {
        self::assertAttributeEquals(
            [
                'stringCacheKey' => 'testValue',
                'arrayCacheKey' => ['testString', 'testString2']
            ],
            '_aCache',
            $oCache
        );

        $oCache->flushCache();

        self::assertAttributeEquals([], '_aCache', $oCache);
    }
}
