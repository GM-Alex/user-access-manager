<?php
/**
 * ObjectHandlerTest.php
 *
 * The ObjectHandlerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\ObjectHandler;

/**
 * Class ObjectHandlerTest
 *
 * @package UserAccessManager\ObjectHandler
 */
class ObjectHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Wordpress
     */
    private function getWrapper()
    {
        return $this->createMock('\UserAccessManager\Wrapper\Wordpress');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Database\Database
     */
    private function getDatabase()
    {
        return $this->createMock('\UserAccessManager\Database\Database');
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\ObjectHandler\ObjectHandler::__construct()
     */
    public function testCanCreateInstance()
    {
        $oObjectHandler = new ObjectHandler(
            $this->getWrapper(),
            $this->getDatabase()
        );

        self::assertInstanceOf('\UserAccessManager\ObjectHandler\ObjectHandler', $oObjectHandler);
    }
}
