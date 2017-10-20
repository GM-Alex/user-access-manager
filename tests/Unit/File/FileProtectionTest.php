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
namespace UserAccessManager\Tests\Unit\File;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\File\FileProtection;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class FileProtectionTest
 *
 * @package UserAccessManager\Tests\Unit\File
 * @coversDefaultClass \UserAccessManager\File\FileProtection
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
     * @param Php             $php
     * @param Wordpress       $wordpress
     * @param WordpressConfig $wordpressConfig
     * @param MainConfig      $mainConfig
     * @param Util            $util
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|FileProtection
     */
    private function getStub(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        Util $util
    ) {
        return $this->getMockForAbstractClass(
            FileProtection::class,
            [$php, $wordpress, $wordpressConfig, $mainConfig, $util]
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
            $this->getUtil()
        );
        self::assertInstanceOf(FileProtection::class, $stub);
    }

    /**
     * @group   unit
     * @covers  ::getDirectoryMatch()
     */
    public function testGetDirectoryMatch()
    {
        $mainConfig = $this->getMainConfig();
        $mainConfig->expects($this->exactly(3))
            ->method('getLockedDirectoryType')
            ->will($this->onConsecutiveCalls('all', 'wordpress', 'custom'));

        $mainConfig->expects($this->once())
            ->method('getCustomLockedDirectories')
            ->will($this->returnValue('customLockedDirectories'));

        $stub = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $mainConfig,
            $this->getUtil()
        );

        self::assertNull(self::callMethod($stub, 'getDirectoryMatch'));
        self::assertEquals('[0-9]{4}'.DIRECTORY_SEPARATOR.'[0-9]{2}', self::callMethod($stub, 'getDirectoryMatch'));
        self::assertEquals('customLockedDirectories', self::callMethod($stub, 'getDirectoryMatch'));
    }

    /**
     * @group   unit
     * @covers  ::cleanUpFileTypes()
     */
    public function testCleanUpFileTypes()
    {
        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->exactly(2))
            ->method('getMimeTypes')
            ->will($this->onConsecutiveCalls(
                ['a' => 'firstType', 'b' => 'firstType', 'c' => 'secondType'],
                ['c' => 'firstType', 'b' => 'firstType', 'a' => 'secondType']
            ));

        $stub = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $wordpressConfig,
            $this->getMainConfig(),
            $this->getUtil()
        );

        self::assertEquals('a|c', self::callMethod($stub, 'cleanUpFileTypes', ['a,c']));
        self::assertEquals('b', self::callMethod($stub, 'cleanUpFileTypes', ['b,f']));
    }

    /**
     * @group   unit
     * @covers  ::createPasswordFile()
     * @covers  ::getDefaultPasswordFileWithPath()
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

        $mainConfig = $this->getMainConfig();
        $mainConfig->expects($this->exactly(2))
            ->method('getFilePassType')
            ->will($this->returnValue(null));

        $util = $this->getUtil();

        $util->expects($this->exactly(2))
            ->method('getRandomPassword')
            ->will($this->returnValue('randomPassword'));

        $stub = $this->getStub(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $mainConfig,
            $util
        );

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

        $mainConfig = $this->getMainConfig();
        $mainConfig->expects($this->exactly(3))
            ->method('getFilePassType')
            ->will($this->returnValue('random'));

        $stub = $this->getStub(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $mainConfig,
            $util
        );
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

        $stub = $this->getStub(
            $this->getPhp(),
            $wordpress,
            $this->getWordpressConfig(),
            $mainConfig,
            $util
        );

        $thirdTestFile = 'vfs://thirdTestDir/'.FileProtection::PASSWORD_FILE_NAME;
        $stub->createPasswordFile(true, 'vfs://thirdTestDir/');
        $content = file_get_contents($thirdTestFile);
        self::assertEquals("userLogin:userPass\n", $content);
    }

    /**
     * @group   unit
     * @covers  ::deleteFiles()
     */
    public function testDeleteFiles()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(6))
            ->method('unlink')
            ->will($this->onConsecutiveCalls(true, true, true, false, false, true));

        $stub = $this->getStub(
            $php,
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getUtil()
        );

        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('testDir', new Directory([
            FileProtection::FILE_NAME => new File('htaccess'),
            FileProtection::PASSWORD_FILE_NAME => new File('password')
        ]));

        $testDir = 'vfs://testDir/';
        $file = $testDir.FileProtection::FILE_NAME;
        $passwordFile = $testDir.FileProtection::PASSWORD_FILE_NAME;

        self::assertTrue(file_exists($file));
        self::assertTrue(file_exists($passwordFile));
        self::assertTrue($stub->deleteFiles($testDir));
        self::assertFalse($stub->deleteFiles($testDir));
        self::assertFalse($stub->deleteFiles($testDir));
    }
}
