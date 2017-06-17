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
namespace UserAccessManager\Cache;

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class CacheProviderFactoryTest
 *
 * @package UserAccessManager\Cache
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

        self::assertInstanceOf('\UserAccessManager\Cache\CacheProviderFactory', $cacheProviderFactory);

        return $cacheProviderFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Cache\CacheProviderFactory::createFileSystemCacheProvider()
     *
     * @param CacheProviderFactory $cacheProviderFactory
     */
    public function testCreateFrom(CacheProviderFactory $cacheProviderFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Cache\FileSystemCacheProvider',
            $cacheProviderFactory->createFileSystemCacheProvider()
        );
    }
}
