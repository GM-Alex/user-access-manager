<?php
/**
 * RedisCacheProviderTest.php
 *
 * The RedisCacheProviderTest unit test class file.
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
use UserAccessManager\Cache\RedisCacheProvider;
use UserAccessManager\Config\StringConfigParameter;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class RedisCacheProviderTest
 *
 * @package UserAccessManager\Tests\Unit\Cache
 * @coversDefaultClass \UserAccessManager\Cache\RedisCacheProvider
 */
class RedisCacheProviderTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     * @covers ::getId()
     * @covers ::init()
     */
    public function testCanCreateInstanceAndGetId()
    {
        $redisCacheProvider = new RedisCacheProvider(
            $this->getWordpress(),
            $this->getConfigFactory(),
            $this->getConfigParameterFactory()
        );

        self::assertInstanceOf(RedisCacheProvider::class, $redisCacheProvider);
        self::assertEquals('RedisCacheProvider', $redisCacheProvider->getId());
        $redisCacheProvider->init();
    }

    /**
     * @group  unit
     * @covers ::getConfig()
     * @throws Exception
     */
    public function testGetConfig()
    {
        $config = $this->getConfig();
        $config->expects($this->once())
            ->method('setDefaultConfigParameters')
            ->with([
                RedisCacheProvider::CONFIG_PREFIX => $this->createMock(StringConfigParameter::class),
                RedisCacheProvider::CONFIG_TTL => $this->createMock(StringConfigParameter::class)
            ]);

        $configFactory = $this->getConfigFactory();
        $configFactory->expects($this->once())
            ->method('createConfig')
            ->with(RedisCacheProvider::CONFIG_KEY)
            ->will($this->returnValue($config));

        $configParameterFactory = $this->getConfigParameterFactory();
        $configParameterFactory->expects($this->exactly(2))
            ->method('createStringConfigParameter')
            ->withConsecutive(
                [RedisCacheProvider::CONFIG_PREFIX, RedisCacheProvider::DEFAULT_PREFIX],
                [RedisCacheProvider::CONFIG_TTL, (string) RedisCacheProvider::DEFAULT_TTL]
            )
            ->will($this->returnValue($this->createMock(StringConfigParameter::class)));

        $redisCacheProvider = new RedisCacheProvider(
            $this->getWordpress(),
            $configFactory,
            $configParameterFactory
        );

        // The second call must return the cached config without recreating it.
        self::assertSame($config, $redisCacheProvider->getConfig());
        self::assertSame($config, $redisCacheProvider->getConfig());
    }

    /**
     * @group  unit
     * @covers ::add()
     * @covers ::get()
     * @covers ::invalidate()
     * @covers ::buildKey()
     * @covers ::getPrefix()
     * @covers ::getTtl()
     */
    public function testAddGetInvalidateWithDefaults()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('wpCacheSet')
            ->with('uam_cache|myKey', 'myValue', 'RedisCacheProvider', 0);
        $wordpress->expects($this->exactly(2))
            ->method('wpCacheGet')
            ->with('uam_cache|myKey', 'RedisCacheProvider')
            ->will($this->onConsecutiveCalls('myValue', false));
        $wordpress->expects($this->once())
            ->method('wpCacheDelete')
            ->with('uam_cache|myKey', 'RedisCacheProvider');

        $redisCacheProvider = new RedisCacheProvider(
            $wordpress,
            $this->getConfigFactory(),
            $this->getConfigParameterFactory()
        );

        $redisCacheProvider->add('myKey', 'myValue');
        self::assertEquals('myValue', $redisCacheProvider->get('myKey'));
        // A cache miss (false) must be normalized to null.
        self::assertNull($redisCacheProvider->get('myKey'));
        $redisCacheProvider->invalidate('myKey');
    }

    /**
     * @group  unit
     * @covers ::add()
     * @covers ::buildKey()
     * @covers ::getPrefix()
     * @covers ::getTtl()
     * @throws Exception
     */
    public function testAddUsesConfiguredPrefixAndTtl()
    {
        $config = $this->getConfig();
        $config->method('getParameterValue')->will($this->returnValueMap([
            [RedisCacheProvider::CONFIG_PREFIX, 'myPrefix'],
            [RedisCacheProvider::CONFIG_TTL, '42']
        ]));

        $configFactory = $this->getConfigFactory();
        $configFactory->method('createConfig')->will($this->returnValue($config));

        $configParameterFactory = $this->getConfigParameterFactory();
        $configParameterFactory->method('createStringConfigParameter')
            ->will($this->returnValue($this->createMock(StringConfigParameter::class)));

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('wpCacheSet')
            ->with('myPrefix|myKey', 'myValue', 'RedisCacheProvider', 42);

        $redisCacheProvider = new RedisCacheProvider($wordpress, $configFactory, $configParameterFactory);

        $redisCacheProvider->getConfig();
        $redisCacheProvider->add('myKey', 'myValue');
    }
}
