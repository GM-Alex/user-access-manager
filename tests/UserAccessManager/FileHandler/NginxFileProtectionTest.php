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
namespace UserAccessManager\FileHandler;

use UserAccessManager\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class NginxFileProtectionTest
 *
 * @package UserAccessManager\FileHandler
 * @coversDefaultClass \UserAccessManager\FileHandler\NginxFileProtection
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
     *
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $nginxFileProtection = new NginxFileProtection(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getUtil()
        );

        self::assertInstanceOf(NginxFileProtection::class, $nginxFileProtection);
    }

    /**
     * @group   unit
     * @covers  ::create()
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

        $config = $this->getMainConfig();

        $config->expects($this->exactly(5))
            ->method('isPermalinksActive')
            ->will($this->onConsecutiveCalls(false, false, true, true, true));

        $config->expects($this->exactly(2))
            ->method('getLockFileTypes')
            ->will($this->onConsecutiveCalls(null, 'selected'));

        $config->expects($this->once())
            ->method('getLockedFileTypes')
            ->will($this->returnValue('png,jpg'));

        $config->expects($this->once())
            ->method('getMimeTypes')
            ->will($this->returnValue(['jpg' => 'firstType']));

        $config->expects($this->exactly(2))
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
            $config,
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
            ->will($this->onConsecutiveCalls(true, true, true, false, false, true));

        $nginxFileProtection = new NginxFileProtection(
            $php,
            $this->getWordpress(),
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
