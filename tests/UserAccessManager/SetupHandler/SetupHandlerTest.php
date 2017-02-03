<?php
/**
 * SetupHandlerTest.php
 *
 * The SetupHandlerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\SetupHandler;

/**
 * Class SetupHandlerTest
 *
 * @package UserAccessManager\SetupHandler
 */
class SetupHandlerTest extends \UserAccessManagerTestCase
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
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\ObjectHandler\ObjectHandler
     */
    private function getObjectHandler()
    {
        return $this->createMock('\UserAccessManager\ObjectHandler\ObjectHandler');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\FileHandler\FileHandler
     */
    private function getFileHandler()
    {
        return $this->createMock('\UserAccessManager\FileHandler\FileHandler');
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::__construct()
     */
    public function testCanCreateInstance()
    {
        $oSetupHandler = new SetupHandler(
            $this->getWrapper(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        self::assertInstanceOf('\UserAccessManager\SetupHandler\SetupHandler', $oSetupHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::_getBlogIds()
     */
    public function testGetBlogIds()
    {
        $aSites = [];

        for ($iCount = 1; $iCount <= 3; $iCount++) {
            $oSite = $this->getMockBuilder('\WP_Site')->getMock();
            $oSite->blog_id = $iCount;
            $aSites[] = $oSite;
        }

        $oWrapper =$this->getWrapper();
        $oWrapper->expects($this->exactly(1))
            ->method('getSites')
            ->will($this->returnValue($aSites));

        $oDatabase = $this->getDatabase();
        $oObjectHandler = $this->getObjectHandler();
        $oFileHandler = $this->getFileHandler();

        $oSetupHandler = new SetupHandler($oWrapper, $oDatabase, $oObjectHandler, $oFileHandler);
        $aBlogIds = self::callMethod($oSetupHandler, '_getBlogIds', []);
        self::assertEquals([1 => 1,  2 => 2,  3 => 3], $aBlogIds);
    }
}
