<?php

namespace UserAccessManager\Controller;

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class LoginControllerTraitTest
 *
 * @package UserAccessManager\Controller
 * @coversDefaultClass \UserAccessManager\Controller\LoginControllerTrait
 */
class LoginControllerTraitTest extends UserAccessManagerTestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoginControllerTrait
     */
    private function getStub()
    {
        return $this->getMockForTrait(LoginControllerTrait::class);
    }

    /**
 * @group  unit
 * @covers ::getUserLogin()
 */
    public function testGetUserLogin()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('escHtml')
            ->with('/log/')
            ->will($this->returnValue('escHtml'));

        $stub = $this->getStub();
        self:self::setValue($stub, 'wordpress', $wordpress);

        $_GET['log'] = '/log\/';

        self::assertEquals('escHtml', $stub->getUserLogin());
    }

    /**
     * @group  unit
     * @covers ::isUserLoggedIn()
     */
    public function testIsUserLoggedIn()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(2))
            ->method('isUserLoggedIn')
            ->will($this->onConsecutiveCalls(true, false));

        $stub = $this->getStub();
        self:self::setValue($stub, 'wordpress', $wordpress);

        self::assertTrue($stub->isUserLoggedIn());
        self::assertFalse($stub->isUserLoggedIn());
    }

    /**
     * @group  unit
     * @covers ::getCurrentUserName()
     */
    public function testGetCurrentUserName()
    {

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->display_name = 'displayName';

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getCurrentUser')
            ->will($this->returnValue($user));

        $stub = $this->getStub();
        self:self::setValue($stub, 'wordpress', $wordpress);

        self::assertEquals('displayName', $stub->getCurrentUserName());
    }

    /**
     * @group  unit
     * @covers ::getLoginUrl()
     * @covers ::getLogoutUrl()
     * @covers ::getRegistrationUrl()
     * @covers ::getLostPasswordUrl()
     */
    public function testGetUrls()
    {
        $_SERVER['REQUEST_URI'] = 'requestUri';

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('wpLoginUrl')
            ->with('requestUri')
            ->will($this->returnValue('loginUrl'));

        $wordpress->expects($this->once())
            ->method('wpLogoutUrl')
            ->with('requestUri')
            ->will($this->returnValue('logoutUrl'));

        $wordpress->expects($this->once())
            ->method('wpRegistrationUrl')
            ->will($this->returnValue('registerUrl'));

        $wordpress->expects($this->once())
            ->method('wpLostPasswordUrl')
            ->with('requestUri')
            ->will($this->returnValue('lostPasswordUrl'));

        $stub = $this->getStub();
        self:self::setValue($stub, 'wordpress', $wordpress);

        self::assertEquals('loginUrl', $stub->getLoginUrl());
        self::assertEquals('logoutUrl', $stub->getLogoutUrl());
        self::assertEquals('registerUrl', $stub->getRegistrationUrl());
        self::assertEquals('lostPasswordUrl', $stub->getLostPasswordUrl());
    }
}
