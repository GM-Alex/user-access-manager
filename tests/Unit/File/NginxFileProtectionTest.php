<?php
/**
 * NginxFileProtectionTest.php
 *
 * The NginxFileProtectionTest unit test class file.
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

use UserAccessManager\File\NginxFileProtection;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class NginxFileProtectionTest
 *
 * @package UserAccessManager\Tests\Unit\File
 * @coversDefaultClass \UserAccessManager\File\NginxFileProtection
 */
class NginxFileProtectionTest extends UserAccessManagerTestCase
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
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $nginxFileProtection = new NginxFileProtection(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getUtil()
        );

        self::assertInstanceOf(NginxFileProtection::class, $nginxFileProtection);
    }

    /**
     * @group  unit
     * @covers ::getFileNameWithPath()
     */
    public function testGetFileNameWithPath()
    {
        $nginxFileProtection = new NginxFileProtection(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getUtil()
        );

        self::assertEquals('ABSPATH'.NginxFileProtection::FILE_NAME, $nginxFileProtection->getFileNameWithPath());
    }

    /**
     * @group   unit
     * @covers  ::create()
     * @covers  ::getPermalinkFileContent()
     * @covers  ::getFileContent()
     */
    public function testCreate()
    {
        $wordpress = $this->getWordpress();

        /**
         * @var \stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->user_login = 'userLogin';
        $user->user_pass = 'userPass';

        $wordpress->expects($this->exactly(2))
            ->method('getCurrentUser')
            ->will($this->returnValue($user));

        $wordpressConfig = $this->getWordpressConfig();

        $wordpressConfig->expects($this->exactly(5))
            ->method('isPermalinksActive')
            ->will($this->onConsecutiveCalls(false, false, true, true, true));

        $wordpressConfig->expects($this->once())
            ->method('getMimeTypes')
            ->will($this->returnValue(['jpg' => 'firstType']));

        $mainConfig = $this->getMainConfig();

        $mainConfig->expects($this->exactly(2))
            ->method('getLockedFileType')
            ->will($this->onConsecutiveCalls(null, 'selected'));

        $mainConfig->expects($this->once())
            ->method('getLockedFiles')
            ->will($this->returnValue('png,jpg'));

        $mainConfig->expects($this->exactly(2))
            ->method('getFilePassType')
            ->will($this->returnValue(null));

        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('testDir', new Directory());
        $testDir = 'vfs://testDir';

        $nginxFileProtection = new NginxFileProtection(
            $this->getPhp(),
            $wordpress,
            $wordpressConfig,
            $mainConfig,
            $this->getUtil()
        );
        $file = 'vfs://testDir/'.NginxFileProtection::FILE_NAME;
        $passwordFile = 'vfs://testDir/'.NginxFileProtection::PASSWORD_FILE_NAME;

        self::assertTrue($nginxFileProtection->create($testDir, null, $testDir));
        self::assertTrue(file_exists($file));
        self::assertTrue(file_exists($passwordFile));
        self::assertEquals(
            "location / {\nauth_basic \"WP-Files\";\nauth_basic_user_file vfs://testDir/.htpasswd;\n}\n",
            file_get_contents($file)
        );
        self::assertEquals(
            "userLogin:userPass\n",
            file_get_contents($passwordFile)
        );

        self::assertTrue($nginxFileProtection->create($testDir, null, $testDir));
        self::assertEquals(
            "location / {\nlocation ~ \.(jpg) {\nauth_basic \"WP-Files\";"
            ."\nauth_basic_user_file vfs://testDir/.htpasswd;\n}\n}\n",
            file_get_contents($file)
        );

        self::assertTrue($nginxFileProtection->create($testDir, null, $testDir));
        self::assertEquals(
            "location / {\n"
            ."rewrite ^([^?]*)$ /index.php?uamfiletype=attachment&uamgetfile=$1 last;\n"
            ."rewrite ^(.*)\\?(((?!uamfiletype).)*)$ /index.php?uamfiletype=attachment&uamgetfile=$1&$2 last;\n"
            ."rewrite ^(.*)\\?(.*)$ /index.php?uamgetfile=$1&$2 last;\n"
            ."}\n",
            file_get_contents($file)
        );

        self::assertTrue($nginxFileProtection->create($testDir, 'objectType', $testDir));
        self::assertEquals(
            "location / {\n"
            ."rewrite ^([^?]*)$ /index.php?uamfiletype=objectType&uamgetfile=$1 last;\n"
            ."rewrite ^(.*)\\?(((?!uamfiletype).)*)$ /index.php?uamfiletype=objectType&uamgetfile=$1&$2 last;\n"
            ."rewrite ^(.*)\\?(.*)$ /index.php?uamgetfile=$1&$2 last;\n"
            ."}\n",
            file_get_contents($file)
        );

        self::assertFalse($nginxFileProtection->create('invalid', 'invalid'));
    }

    /**
     * @group   unit
     * @covers  ::delete()
     */
    public function testDelete()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(6))
            ->method('unlink')
            ->withConsecutive(
                ['vfs://testDir/uam.conf'],
                ['vfs://testDir/.htpasswd'],
                ['vfs://testDir/uam.conf'],
                ['vfs://testDir/.htpasswd'],
                ['vfs://testDir/uam.conf'],
                ['vfs://testDir/.htpasswd']
            )
            ->will($this->onConsecutiveCalls(true, true, true, false, false, true));

        $nginxFileProtection = new NginxFileProtection(
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
            NginxFileProtection::FILE_NAME => new File('empty'),
            NginxFileProtection::PASSWORD_FILE_NAME => new File('empty')
        ]));

        $testDir = 'vfs://testDir/';
        $file = $testDir.NginxFileProtection::FILE_NAME;
        $passwordFile = $testDir.NginxFileProtection::PASSWORD_FILE_NAME;

        self::assertTrue(file_exists($file));
        self::assertTrue(file_exists($passwordFile));
        self::assertTrue($nginxFileProtection->delete($testDir));
        self::assertFalse($nginxFileProtection->delete($testDir));
        self::assertFalse($nginxFileProtection->delete($testDir));
    }
}
