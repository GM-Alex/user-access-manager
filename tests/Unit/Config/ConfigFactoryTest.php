<?php

namespace UserAccessManager\Tests\Unit\Config;

use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigFactory;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * @coversDefaultClass \UserAccessManager\Config\ConfigFactory
 */
class ConfigFactoryTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
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
     */
    public function testCreateApacheFileProtection(ConfigFactory $configFactory)
    {
        $fileObject = $configFactory->createConfig('key');
        self::assertInstanceOf(Config::class, $fileObject);
    }
}
