<?php
/**
 * FileProtectionTest.php
 *
 * The FileProtectionTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\FileHandler;

use Vfs\FileSystem;
use Vfs\Node\Directory;

/**
 * Class FileProtectionTest
 *
 * @package UserAccessManager\FileHandler
 */
class FileProtectionTest extends \UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private $oRoot;

    /**
     * Setup virtual file system.
     */
    public function setUp()
    {
        $this->oRoot = FileSystem::factory('vfs://');
        $this->oRoot->mount();
    }

    /**
     * Tear down virtual file system.
     */
    public function tearDown()
    {
        $this->oRoot->unmount();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Wordpress
     */
    private function getWrapper()
    {
        return $this->createMock('\UserAccessManager\Wrapper\Wordpress');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\Config
     */
    private function getConfig()
    {
        return $this->createMock('\UserAccessManager\Config\Config');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Util\Util
     */
    private function getUtil()
    {
        return $this->createMock('\UserAccessManager\Util\Util');
    }

    /**
     * @param $oWrapper
     * @param $oConfig
     * @param $oUtil
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|FileProtection
     */
    private function getStub($oWrapper, $oConfig, $oUtil)
    {
        return $this->getMockForAbstractClass(
            '\UserAccessManager\FileHandler\FileProtection',
            [$oWrapper, $oConfig, $oUtil]
        );
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\FileHandler\FileProtection::__construct()
     */
    public function testCanCreateInstance()
    {
        $oWrapper = $this->getWrapper();
        $oConfig = $this->getConfig();
        $oUtil = $this->getUtil();
        $oStub = $this->getStub($oWrapper, $oConfig, $oUtil);
        self::assertInstanceOf('\UserAccessManager\FileHandler\FileProtection', $oStub);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\FileHandler\FileProtection::_cleanUpFileTypes()
     */
    public function testCleanUpFileTypes()
    {
        $oWrapper = $this->getWrapper();
        $oConfig = $this->getConfig();
        $oConfig->expects($this->exactly(2))
            ->method('getMimeTypes')
            ->will($this->onConsecutiveCalls(
                ['a' => 'firstType', 'b' => 'firstType', 'c' => 'secondType'],
                ['c' => 'firstType', 'b' => 'firstType', 'a' => 'secondType']
            ));
        $oUtil = $this->getUtil();
        $oStub = $this->getStub($oWrapper, $oConfig, $oUtil);

        self::assertEquals('a|c', self::callMethod($oStub, '_cleanUpFileTypes', ['a,c']));
        self::assertEquals('b', self::callMethod($oStub, '_cleanUpFileTypes', ['b,f']));
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\FileHandler\FileProtection::createPasswordFile()
     */
    public function testCreatePasswordFile()
    {
        /**
         * @var Directory $oRootDir
         */
        $oRootDir = $this->oRoot->get('/');
        $oRootDir->add('firstTestDir', new Directory());
        $oRootDir->add('secondTestDir', new Directory());

        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(6))
            ->method('getUploadDir')
            ->will(
                $this->onConsecutiveCalls(
                    ['error' => 'error', 'basedir' => 'vfs://firstTestDir'],
                    ['basedir' => 'vfs://firstTestDir'],
                    ['basedir' => 'vfs://firstTestDir'],
                    ['basedir' => 'vfs://firstTestDir'],
                    ['basedir' => 'vfs://firstTestDir'],
                    ['basedir' => 'vfs://firstTestDir']
                )
            );

        /**
         * @var \stdClass $oUser
         */
        $oUser = $this->getMockBuilder('\WP_User')->getMock();
        $oUser->user_login = 'userLogin';
        $oUser->user_pass = 'userPass';

        $oWrapper->expects($this->any())
            ->method('getCurrentUser')
            ->will($this->returnValue($oUser));

        $oConfig = $this->getConfig();
        $oConfig->expects($this->any())
            ->method('getFilePassType')
            ->will($this->returnValue(null));

        $oUtil = $this->getUtil();

        $oUtil->expects($this->any())
            ->method('getRandomPassword')
            ->will($this->returnValue('randomPassword'));

        $oStub = $this->getStub($oWrapper, $oConfig, $oUtil);

        $sFirstTestFile = 'vfs://firstTestDir/'.FileProtection::PASSWORD_FILE_NAME;
        $oStub->createPasswordFile();
        $this->assertFalse(file_exists($sFirstTestFile));

        $oStub->createPasswordFile();
        $this->assertTrue(file_exists($sFirstTestFile));
        $sContent = file_get_contents($sFirstTestFile);
        self::assertEquals("userLogin:userPass\n", $sContent);

        $oStub->createPasswordFile();
        self::assertEquals($sContent, file_get_contents($sFirstTestFile));

        $oStub->createPasswordFile(true);
        self::assertEquals($sContent, file_get_contents($sFirstTestFile));

        $oConfig = $this->getConfig();
        $oConfig->expects($this->any())
            ->method('getFilePassType')
            ->will($this->returnValue('random'));

        $oStub = $this->getStub($oWrapper, $oConfig, $oUtil);
        $oStub->createPasswordFile();
        self::assertEquals($sContent, file_get_contents($sFirstTestFile));

        $oStub->createPasswordFile(true);
        self::assertEquals("userLogin:".md5('randomPassword')."\n", file_get_contents($sFirstTestFile));
        self::assertNotEquals($sContent, file_get_contents($sFirstTestFile));

        $sSecondTestFile = 'vfs://secondTestDir/'.FileProtection::PASSWORD_FILE_NAME;
        $oStub->createPasswordFile(true, 'vfs://secondTestDir/');
        self::assertEquals("userLogin:".md5('randomPassword')."\n", file_get_contents($sSecondTestFile));
    }
}
