<?php
/**
 * ControllerTest.php
 *
 * The ControllerTest unit test class file.
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

use stdClass;
use UserAccessManager\Controller\Frontend\FrontendController;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\UserAccessManager;
use UserAccessManager\UserGroup\UserGroupTypeException;

/**
 * Class ControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Frontend
 * @coversDefaultClass \UserAccessManager\Controller\Frontend\FrontendController
 */
class FrontendControllerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $frontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getAccessHandler()
        );

        self::assertInstanceOf(FrontendController::class, $frontendController);
    }

    /**
     * @group  unit
     * @covers ::enqueueStylesAndScripts()
     * @covers ::registerStylesAndScripts()
     */
    public function testEnqueueStylesAndScripts()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('registerStyle')
            ->with(
                FrontendController::HANDLE_STYLE_LOGIN_FORM,
                'http://url/assets/css/uamLoginForm.css',
                [],
                UserAccessManager::VERSION,
                'screen'
            );

        $wordpress->expects($this->once())
            ->method('enqueueStyle')
            ->with(FrontendController::HANDLE_STYLE_LOGIN_FORM);

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->once())
            ->method('getUrlPath')
            ->will($this->returnValue('http://url/'));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $wordpress,
            $wordpressConfig,
            $this->getMainConfig(),
            $this->getAccessHandler()
        );

        $frontendController->enqueueStylesAndScripts();
    }

    /**
     * @group  unit
     * @covers ::showAncestors()
     * @throws UserGroupTypeException
     */
    public function testShowAncestors()
    {
        $mainConfig = $this->getMainConfig();

        $mainConfig->expects($this->exactly(2))
            ->method('lockRecursive')
            ->will($this->onConsecutiveCalls(true, true));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(5))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['objectType', 'objectId'],
                ['objectType', 'objectId'],
                ['objectType', 1],
                ['objectType', 2],
                ['objectType', 3]
            )
            ->will($this->onConsecutiveCalls(
                false,
                true,
                true,
                false,
                true
            ));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $mainConfig,
            $accessHandler
        );

        $ancestors = [
            1 => 1,
            2 => 2,
            3 => 3
        ];

        self::assertEquals([], $frontendController->showAncestors($ancestors, 'objectId', 'objectType'));
        self::assertEquals(
            [1 => 1, 3 => 3],
            $frontendController->showAncestors($ancestors, 'objectId', 'objectType')
        );
    }

    /**
     * @group  unit
     * @covers ::getWpSeoUrl()
     * @throws UserGroupTypeException
     */
    public function testGetWpSeoUrl()
    {
        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(2))
            ->method('checkObjectAccess')
            ->withConsecutive(
                ['type', 1],
                ['type', 1]
            )
            ->will($this->onConsecutiveCalls(
                true,
                false
            ));

        $frontendController = new FrontendController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $accessHandler
        );

        $object = new stdClass();
        $object->ID = 1;

        self::assertEquals('url', $frontendController->getWpSeoUrl('url', 'type', $object));
        self::assertFalse($frontendController->getWpSeoUrl('url', 'type', $object));
    }
}
