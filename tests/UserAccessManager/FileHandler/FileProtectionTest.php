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
 * @version   SVN: $id$
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
 * @coversDefaultClass \UserAccessManager\FileHandler\FileProtection
 */
class FileProtectionTest extends UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private $root;

    /**
     * Setup virtual file system.
     */
    public function setUp()
    {
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();
    }

    /**
     * Tear down virtual file system.
     */
    public function tearDown()
    {
        $this->root->unmount();
    }

    /**
     * @param $php
     * @param $wordpress
     * @param $config
     * @param $util
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|FileProtection
     */
    private function getStub($php, $wordpress, $config, $util)
    {
        return $this->getMockForAbstractClass(
            '\UserAccessManager\FileHandler\FileProtection',
            [$php, $wordpress, $config, $util]
        );
    }

    /**
     * @group   unit
     * @covers  ::__construct()
     */
    public function testCanCreateInstance()
    {
        $php = $this->getPhp();
        $wordpress = $this->getWordpress();
        $config = $this->getMainConfig();
        $util = $this->getUtil();
        $stub = $this->getStub($php, $wordpress, $config, $util);
        self::assertInstanceOf(FileProtection::class, $stub);
    }

    /**
     * @group   unit
     * @covers  ::cleanUpFileTypes()
     */
    public function testCleanUpFileTypes()
    {
        $php = $this->getPhp();
        $wordpress = $this->getWordpress();
        $config = $this->getMainConfig();
        $config->expects($this->exactly(2))
            ->method('getMimeTypes')
            ->will($this->onConsecutiveCalls(
                ['a' => 'firstType', 'b' => 'firstType', 'c' => 'secondType'],
                ['c' => 'firstType', 'b' => 'firstType', 'a' => 'secondType']
            ));
        $util = $this->getUtil();
        $stub = $this->getStub($php, $wordpress, $config, $util);

        self::assertEquals('a|c', self::callMethod($stub, 'cleanUpFileTypes', ['a,c']));
        self::assertEquals('b', self::callMethod($stub, 'cleanUpFileTypes', ['b,f']));
    }

    /**
     * @group   unit
     * @covers  ::createPasswordFile()
     */
    public function testCreatePasswordFile()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('firstTestDir', new Directory());
        $rootDir->add('secondTestDir', new Directory());
        $rootDir->add('thirdTestDir', new Directory());

        $php = $this->getPhp();

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(6))
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
         * @var \stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->user_login = 'userLogin';
        $user->user_pass = 'userPass';

        $wordpress->expects($this->exactly(5))
            ->method('getCurrentUser')
            ->will($this->returnValue($user));

        $config = $this->getMainConfig();
        $config->expects($this->exactly(2))
            ->method('getFilePassType')
            ->will($this->returnValue(null));

        $util = $this->getUtil();

        $util->expects($this->exactly(2))
            ->method('getRandomPassword')
            ->will($this->returnValue('randomPassword'));

        $stub = $this->getStub($php, $wordpress, $config, $util);

        $firstTestFile = 'vfs://firstTestDir/'.FileProtection::PASSWORD_FILE_NAME;
        $stub->createPasswordFile();
        $this->assertFalse(file_exists($firstTestFile));

        $stub->createPasswordFile();
        $this->assertTrue(file_exists($firstTestFile));
        $content = file_get_contents($firstTestFile);
        self::assertEquals("userLogin:userPass\n", $content);

        $stub->createPasswordFile();
        self::assertEquals($content, file_get_contents($firstTestFile));

        $stub->createPasswordFile(true);
        self::assertEquals($content, file_get_contents($firstTestFile));

        $config = $this->getMainConfig();
        $config->expects($this->exactly(3))
            ->method('getFilePassType')
            ->will($this->returnValue('random'));

        $stub = $this->getStub($php, $wordpress, $config, $util);
        $stub->createPasswordFile();
        self::assertEquals($content, file_get_contents($firstTestFile));

        $stub->createPasswordFile(true);
        self::assertEquals("userLogin:".md5('randomPassword')."\n", file_get_contents($firstTestFile));
        self::assertNotEquals($content, file_get_contents($firstTestFile));

        $secondTestFile = 'vfs://secondTestDir/'.FileProtection::PASSWORD_FILE_NAME;
        $stub->createPasswordFile(true, 'vfs://secondTestDir/');
        self::assertEquals("userLogin:".md5('randomPassword')."\n", file_get_contents($secondTestFile));

        $util = $this->getUtil();
        $util->expects($this->exactly(1))
            ->method('getRandomPassword')
            ->will($this->returnCallback(function () {
                throw new \Exception('Unable to generate secure token from OpenSSL.');
            }));

        $stub = $this->getStub($php, $wordpress, $config, $util);

        $thirdTestFile = 'vfs://thirdTestDir/'.FileProtection::PASSWORD_FILE_NAME;
        $stub->createPasswordFile(true, 'vfs://thirdTestDir/');
        $content = file_get_contents($thirdTestFile);
        self::assertEquals("userLogin:userPass\n", $content);
    }
}
