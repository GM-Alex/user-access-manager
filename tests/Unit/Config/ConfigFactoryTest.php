<?php
/**
 * ConfigFactoryTest.php
 *
 * The ConfigFactoryTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Config;

use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigFactory;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class ConfigFactoryTest
 *
 * @package UserAccessManager\Tests\Unit\Config
 * @coversDefaultClass \UserAccessManager\Config\ConfigFactory
 */
class ConfigFactoryTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     * @return ConfigFactory
     */
    public function testCanCreateInstance(): ConfigFactory
    {
        $configFactory = new ConfigFactory($this->getWordpress());
        self::assertInstanceOf(ConfigFactory::class, $configFactory);

        return $configFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createConfig()
     * @param ConfigFactory $configFactory
     */
    public function testCreateApacheFileProtection(ConfigFactory $configFactory)
    {
        $fileObject = $configFactory->createConfig('key');
        self::assertInstanceOf(Config::class, $fileObject);
    }
}
