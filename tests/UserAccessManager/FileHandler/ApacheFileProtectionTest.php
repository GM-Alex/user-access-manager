<?php
/**
 * ApacheFileProtectionTest.php
 *
 * The ApacheFileProtectionTest unit test class file.
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
 * Class ApacheFileProtectionTest
 *
 * @package UserAccessManager\FileHandler
 */
class ApacheFileProtectionTest extends UserAccessManagerTestCase
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
     * @covers \UserAccessManager\FileHandler\ApacheFileProtection::__construct()
     */
    public function testCanCreateInstance()
    {
        $apacheFileProtection = new ApacheFileProtection(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getUtil()
        );

        self::assertInstanceOf('\UserAccessManager\FileHandler\ApacheFileProtection', $apacheFileProtection);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\FileHandler\ApacheFileProtection::create()
     */
    public function testCreate()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(3))
            ->method('getHomeUrl')
            ->will($this->returnValue('http://www.test.com'));

        /**
         * @var \stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->user_login = 'userLogin';
        $user->user_pass = 'userPass';

        $wordpress->expects($this->exactly(3))
            ->method('getCurrentUser')
            ->will($this->returnValue($user));

        $config = $this->getMainConfig();
        $util = $this->getUtil();

        $config->expects($this->exactly(6))
            ->method('isPermalinksActive')
            ->will($this->onConsecutiveCalls(false, false, false, true, true, true));

        $config->expects($this->exactly(6))
            ->method('getLockFileTypes')
            ->will($this->onConsecutiveCalls(null, 'selected', 'not_selected', null, 'selected', null));

        $config->expects($this->exactly(2))
            ->method('getLockedFileTypes')
            ->will($this->returnValue('png,jpg'));

        $config->expects($this->once())
            ->method('getNotLockedFileTypes')
            ->will($this->returnValue('png,jpg'));

        $config->expects($this->exactly(3))
            ->method('getMimeTypes')
            ->will($this->returnValue(['jpg' => 'firstType']));

        $config->expects($this->exactly(3))
            ->method('getFilePassType')
            ->will($this->returnValue(null));

        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('testDir', new Directory());
        $testDir = 'vfs://testDir';

        $apacheFileProtection = new ApacheFileProtection(
            $this->getPhp(),
            $wordpress,
            $config,
            $util
        );

        $file = 'vfs://testDir/'.ApacheFileProtection::FILE_NAME;
        $passwordFile = 'vfs://testDir/'.ApacheFileProtection::PASSWORD_FILE_NAME;

        self::assertTrue($apacheFileProtection->create($testDir));
        self::assertTrue(file_exists($file));
        self::assertTrue(file_exists($passwordFile));
        self::assertEquals(
            "AuthType Basic\nAuthName \"WP-Files\"\nAuthUserFile vfs://testDir/.htpasswd\nrequire valid-user\n",
            file_get_contents($file)
        );
        self::assertEquals(
            "userLogin:userPass\n",
            file_get_contents($passwordFile)
        );

        self::assertTrue($apacheFileProtection->create($testDir));
        self::assertEquals(
            "<FilesMatch '\.(jpg)'>\n"
            ."AuthType Basic\nAuthName \"WP-Files\"\n"
            ."AuthUserFile vfs://testDir/.htpasswd\nrequire valid-user\n"
            ."</FilesMatch>\n",
            file_get_contents($file)
        );

        self::assertTrue($apacheFileProtection->create($testDir));
        self::assertEquals(
            "<FilesMatch '^\.(jpg)'>"
            ."\nAuthType Basic\nAuthName \"WP-Files\"\n"
            ."AuthUserFile vfs://testDir/.htpasswd\nrequire valid-user\n"
            ."</FilesMatch>\n",
            file_get_contents($file)
        );

        self::assertTrue($apacheFileProtection->create($testDir));
        self::assertEquals(
            "<IfModule mod_rewrite.c>\n"
            ."RewriteEngine On\nRewriteBase /\nRewriteRule ^index\.php$ - [L]\n"
            ."RewriteRule ^([^?]*)$ /index.php?uamfiletype=attachment&uamgetfile=$1 [QSA,L]\n"
            ."RewriteRule ^(.*)\\?(((?!uamfiletype).)*)$ /index.php?uamfiletype=attachment&uamgetfile=$1&$2 [QSA,L]\n"
            ."RewriteRule ^(.*)\\?(.*)$ /index.php?uamgetfile=$1&$2 [QSA,L]\n"
            ."</IfModule>\n",
            file_get_contents($file)
        );

        self::assertTrue($apacheFileProtection->create($testDir, 'objectType'));
        self::assertEquals(
            "<IfModule mod_rewrite.c>\n"
            ."<FilesMatch '\.(jpg)'>\n"
            ."RewriteEngine On\nRewriteBase /\nRewriteRule ^index\.php$ - [L]\n"
            ."RewriteRule ^([^?]*)$ /index.php?uamfiletype=objectType&uamgetfile=$1 [QSA,L]\n"
            ."RewriteRule ^(.*)\\?(((?!uamfiletype).)*)$ /index.php?uamfiletype=objectType&uamgetfile=$1&$2 [QSA,L]\n"
            ."RewriteRule ^(.*)\\?(.*)$ /index.php?uamgetfile=$1&$2 [QSA,L]\n"
            ."</FilesMatch>\n"
            ."</IfModule>\n",
            file_get_contents($file)
        );

        self::assertFalse($apacheFileProtection->create('invalid', 'invalid'));
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\FileHandler\ApacheFileProtection::delete()
     */
    public function testDelete()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(6))
            ->method('unlink')
            ->will($this->onConsecutiveCalls(true, true, true, false, false, true));

        $apacheFileProtection = new ApacheFileProtection(
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
            ApacheFileProtection::FILE_NAME => new File('htaccess'),
            ApacheFileProtection::PASSWORD_FILE_NAME => new File('password')
        ]));

        $testDir = 'vfs://testDir/';
        $file = $testDir.ApacheFileProtection::FILE_NAME;
        $passwordFile = $testDir.ApacheFileProtection::PASSWORD_FILE_NAME;

        self::assertTrue(file_exists($file));
        self::assertTrue(file_exists($passwordFile));
        self::assertTrue($apacheFileProtection->delete($testDir));
        self::assertFalse($apacheFileProtection->delete($testDir));
        self::assertFalse($apacheFileProtection->delete($testDir));
    }
}
