<?php
/**
 * LoginControllerTraitTest.php
 *
 * The LoginControllerTraitTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Controller\Frontend;

use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use UserAccessManager\Controller\Frontend\LoginControllerTrait;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class LoginControllerTraitTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Frontend
 * @coversDefaultClass \UserAccessManager\Controller\Frontend\LoginControllerTrait
 */
class LoginControllerTraitTest extends UserAccessManagerTestCase
{
    /**
     * @return MockObject|LoginControllerTrait
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

        $stub->expects($this->any())
            ->method('getWordpress')
            ->will($this->returnValue($wordpress));

        $stub->expects($this->any())
            ->method('getRequestParameter')
            ->will($this->returnValue('/log\/'));

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

        $stub->expects($this->any())
            ->method('getWordpress')
            ->will($this->returnValue($wordpress));

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
         * @var MockObject|stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->display_name = 'displayName';

        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getCurrentUser')
            ->will($this->returnValue($user));

        $stub = $this->getStub();

        $stub->expects($this->any())
            ->method('getWordpress')
            ->will($this->returnValue($wordpress));

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

        $stub->expects($this->any())
            ->method('getWordpress')
            ->will($this->returnValue($wordpress));

        $stub->expects($this->any())
            ->method('getRequestUrl')
            ->will($this->returnValue('requestUri'));

        self::assertEquals('loginUrl', $stub->getLoginUrl());
        self::assertEquals('logoutUrl', $stub->getLogoutUrl());
        self::assertEquals('registerUrl', $stub->getRegistrationUrl());
        self::assertEquals('lostPasswordUrl', $stub->getLostPasswordUrl());
    }

    /**
     * @group  unit
     * @covers ::showLoginForm()
     */
    public function testShowLoginForm()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(4))
            ->method('isSingle')
            ->will($this->onConsecutiveCalls(true, false, true, false));

        $wordpress->expects($this->exactly(2))
            ->method('isPage')
            ->will($this->onConsecutiveCalls(false, true));

        $stub = $this->getStub();

        $stub->expects($this->any())
            ->method('getWordpress')
            ->will($this->returnValue($wordpress));

        self::assertTrue($stub->showLoginForm());
        self::assertFalse($stub->showLoginForm());
        self::assertTrue($stub->showLoginForm());
        self::assertTrue($stub->showLoginForm());
    }

    /**
     * @group  unit
     * @covers ::getRedirectLoginUrl()
     */
    public function testGetRedirectLoginUrl()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getBlogInfo')
            ->with('wpurl')
            ->will($this->returnValue('BlogInfo'));

        $wordpress->expects($this->once())
            ->method('applyFilters')
            ->with('uam_login_url', 'BlogInfo/wp-login.php?redirect_to=uri%40')
            ->will($this->returnValue('filter'));

        $stub = $this->getStub();

        $stub->expects($this->any())
            ->method('getWordpress')
            ->will($this->returnValue($wordpress));

        $_SERVER['REQUEST_URI'] = 'uri@';

        self::assertEquals('filter', $stub->getRedirectLoginUrl());
    }
}
