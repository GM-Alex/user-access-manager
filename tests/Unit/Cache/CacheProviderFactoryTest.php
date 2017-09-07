<?php
/**
 * CacheProviderFactoryTest.php
 *
 * The CacheProviderFactoryTest unit test class file.
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

use UserAccessManager\Cache\CacheProviderFactory;
use UserAccessManager\Cache\FileSystemCacheProvider;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class CacheProviderFactoryTest
 *
 * @package UserAccessManager\Tests\Unit\Cache
 * @coversDefaultClass \UserAccessManager\Cache\CacheProviderFactory
 */
class CacheProviderFactoryTest extends UserAccessManagerTestCase
{
    /**
     * @group unit
     *
     * @return CacheProviderFactory
     */
    public function testCanCreateInstance()
    {
        $cacheProviderFactory = new CacheProviderFactory(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getUtil(),
            $this->getConfigFactory(),
            $this->getConfigParameterFactory()
        );

        self::assertInstanceOf(CacheProviderFactory::class, $cacheProviderFactory);

        return $cacheProviderFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createFileSystemCacheProvider()
     *
     * @param CacheProviderFactory $cacheProviderFactory
     */
    public function testCreateFrom(CacheProviderFactory $cacheProviderFactory)
    {
        self::assertInstanceOf(
            FileSystemCacheProvider::class,
            $cacheProviderFactory->createFileSystemCacheProvider()
        );
    }
}
