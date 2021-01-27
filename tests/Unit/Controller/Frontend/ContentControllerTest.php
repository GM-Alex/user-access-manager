<?php

namespace UserAccessManager\Tests\Unit\Controller\Frontend;

use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Frontend\ContentController;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class ContentControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Frontend
 * @coversDefaultClass \UserAccessManager\Controller\Frontend\ContentController
 */
class ContentControllerTest extends UserAccessManagerTestCase
{
    /**
     * @param Php $php
     * @param Wordpress $wordpress
     * @param WordpressConfig $wordpressConfig
     * @param MainConfig $mainConfig
     * @param Util $util
     * @param ObjectHandler $objectHandler
     * @param UserHandler $userHandler
     * @param UserGroupHandler $userGroupHandler
     * @param AccessHandler $accessHandler
     * @return MockObject|ContentController
     */
    private function getStub(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        Util $util,
        ObjectHandler $objectHandler,
        UserHandler $userHandler,
        UserGroupHandler $userGroupHandler,
        AccessHandler $accessHandler
    )
    {
        return $this->getMockForAbstractClass(
            ContentController::class,
            [
                $php,
                $wordpress,
                $wordpressConfig,
                $mainConfig,
                $util,
                $objectHandler,
                $userHandler,
                $userGroupHandler,
                $accessHandler
            ]
        );
    }

    /**
     * @group   unit
     * @covers  ::__construct()
     */
    public function testCanCreateInstance()
    {
        $stub = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getAccessHandler()
        );
        self::assertInstanceOf(ContentController::class, $stub);
    }

    /**
     * @group  unit
     * @covers ::getWordpress()
     * @covers ::getMainConfig()
     * @covers ::getUtil()
     * @covers ::getUserHandler()
     * @covers ::getUserGroupHandler()
     * @throws ReflectionException
     */
    public function testSimpleGetters()
    {
        $stub = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getAccessHandler()
        );

        self::assertEquals($this->getWordpress(), self::callMethod($stub, 'getWordpress'));
        self::assertEquals($this->getMainConfig(), self::callMethod($stub, 'getMainConfig'));
        self::assertEquals($this->getUtil(), self::callMethod($stub, 'getUtil'));
        self::assertEquals($this->getUserHandler(), self::callMethod($stub, 'getUserHandler'));
        self::assertEquals($this->getUserGroupHandler(), self::callMethod($stub, 'getUserGroupHandler'));
    }

    /**
     * @group  unit
     * @covers ::removePostFromList()
     * @throws ReflectionException
     */
    public function testRemovePostFromList()
    {
        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->exactly(2))
            ->method('atAdminPanel')
            ->will($this->onConsecutiveCalls(false, true));

        $mainConfig = $this->getMainConfig();
        $mainConfig->expects($this->exactly(3))
            ->method('hidePostType')
            ->with('postType')
            ->will($this->onConsecutiveCalls(false, false, true));

        $stub = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $wordpressConfig,
            $mainConfig,
            $this->getUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getAccessHandler()
        );

        self::assertFalse(self::callMethod($stub, 'removePostFromList', ['postType']));
        self::assertTrue(self::callMethod($stub, 'removePostFromList', ['postType']));
        self::assertTrue(self::callMethod($stub, 'removePostFromList', ['postType']));
    }
}
