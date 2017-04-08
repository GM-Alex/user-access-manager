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

use UserAccessManager\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;

/**
 * Class FileProtectionTest
 *
 * @package UserAccessManager\FileHandler
 */
class FileProtectionTest extends UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private $Root;

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
     * @param $Php
     * @param $Wordpress
     * @param $Config
     * @param $Util
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|FileProtection
     */
    private function getStub($Php, $Wordpress, $Config, $Util)
    {
        return $this->getMockForAbstractClass(
            '\UserAccessManager\FileHandler\FileProtection',
            [$Php, $Wordpress, $Config, $Util]
        );
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\FileHandler\FileProtection::__construct()
     */
    public function testCanCreateInstance()
    {
        $Php = $this->getPhp();
        $Wordpress = $this->getWordpress();
        $Config = $this->getConfig();
        $Util = $this->getUtil();
        $Stub = $this->getStub($Php, $Wordpress, $Config, $Util);
        self::assertInstanceOf('\UserAccessManager\FileHandler\FileProtection', $Stub);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\FileHandler\FileProtection::cleanUpFileTypes()
     */
    public function testCleanUpFileTypes()
    {
        $Php = $this->getPhp();
        $Wordpress = $this->getWordpress();
        $Config = $this->getConfig();
        $Config->expects($this->exactly(2))
            ->method('getMimeTypes')
            ->will($this->onConsecutiveCalls(
                ['a' => 'firstType', 'b' => 'firstType', 'c' => 'secondType'],
                ['c' => 'firstType', 'b' => 'firstType', 'a' => 'secondType']
            ));
        $Util = $this->getUtil();
        $Stub = $this->getStub($Php, $Wordpress, $Config, $Util);

        self::assertEquals('a|c', self::callMethod($Stub, 'cleanUpFileTypes', ['a,c']));
        self::assertEquals('b', self::callMethod($Stub, 'cleanUpFileTypes', ['b,f']));
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\FileHandler\FileProtection::createPasswordFile()
     */
    public function testCreatePasswordFile()
    {
        /**
         * @var Directory $RootDir
         */
        $RootDir = $this->oRoot->get('/');
        $RootDir->add('firstTestDir', new Directory());
        $RootDir->add('secondTestDir', new Directory());
        $RootDir->add('thirdTestDir', new Directory());

        $Php = $this->getPhp();

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(6))
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
         * @var \stdClass $User
         */
        $User = $this->getMockBuilder('\WP_User')->getMock();
        $User->user_login = 'userLogin';
        $User->user_pass = 'userPass';

        $Wordpress->expects($this->exactly(5))
            ->method('getCurrentUser')
            ->will($this->returnValue($User));

        $Config = $this->getConfig();
        $Config->expects($this->exactly(2))
            ->method('getFilePassType')
            ->will($this->returnValue(null));

        $Util = $this->getUtil();

        $Util->expects($this->exactly(2))
            ->method('getRandomPassword')
            ->will($this->returnValue('randomPassword'));

        $Stub = $this->getStub($Php, $Wordpress, $Config, $Util);

        $sFirstTestFile = 'vfs://firstTestDir/'.FileProtection::PASSWORD_FILE_NAME;
        $Stub->createPasswordFile();
        $this->assertFalse(file_exists($sFirstTestFile));

        $Stub->createPasswordFile();
        $this->assertTrue(file_exists($sFirstTestFile));
        $sContent = file_get_contents($sFirstTestFile);
        self::assertEquals("userLogin:userPass\n", $sContent);

        $Stub->createPasswordFile();
        self::assertEquals($sContent, file_get_contents($sFirstTestFile));

        $Stub->createPasswordFile(true);
        self::assertEquals($sContent, file_get_contents($sFirstTestFile));

        $Config = $this->getConfig();
        $Config->expects($this->exactly(3))
            ->method('getFilePassType')
            ->will($this->returnValue('random'));

        $Stub = $this->getStub($Php, $Wordpress, $Config, $Util);
        $Stub->createPasswordFile();
        self::assertEquals($sContent, file_get_contents($sFirstTestFile));

        $Stub->createPasswordFile(true);
        self::assertEquals("userLogin:".md5('randomPassword')."\n", file_get_contents($sFirstTestFile));
        self::assertNotEquals($sContent, file_get_contents($sFirstTestFile));

        $sSecondTestFile = 'vfs://secondTestDir/'.FileProtection::PASSWORD_FILE_NAME;
        $Stub->createPasswordFile(true, 'vfs://secondTestDir/');
        self::assertEquals("userLogin:".md5('randomPassword')."\n", file_get_contents($sSecondTestFile));

        $Util = $this->getUtil();
        $Util->expects($this->exactly(1))
            ->method('getRandomPassword')
            ->will($this->returnCallback(function () {
                throw new \Exception('Unable to generate secure token from OpenSSL.');
            }));

        $Stub = $this->getStub($Php, $Wordpress, $Config, $Util);

        $sThirdTestFile = 'vfs://thirdTestDir/'.FileProtection::PASSWORD_FILE_NAME;
        $Stub->createPasswordFile(true, 'vfs://thirdTestDir/');
        $sContent = file_get_contents($sThirdTestFile);
        self::assertEquals("userLogin:userPass\n", $sContent);
    }
}
