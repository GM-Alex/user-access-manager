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
namespace UserAccessManager\Tests\Unit\File;

use UserAccessManager\File\ApacheFileProtection;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class ApacheFileProtectionTest
 *
 * @package UserAccessManager\Tests\Unit\File
 * @coversDefaultClass \UserAccessManager\File\ApacheFileProtection
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
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $apacheFileProtection = new ApacheFileProtection(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getUtil()
        );

        self::assertInstanceOf(ApacheFileProtection::class, $apacheFileProtection);
    }

    /**
     * @group  unit
     * @covers ::getFileNameWithPath()
     */
    public function testGetFileNameWithPath()
    {
        $nginxFileProtection = new ApacheFileProtection(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getUtil()
        );

        self::assertEquals(ApacheFileProtection::FILE_NAME, $nginxFileProtection->getFileNameWithPath());
        self::assertEquals('dir/'.ApacheFileProtection::FILE_NAME, $nginxFileProtection->getFileNameWithPath('dir/'));
    }

    /**
     * @group   unit
     * @covers  ::create()
     * @covers  ::getPermalinkFileContent()
     * @covers  ::getFileContent()
     * @covers  ::applyFilters()
     * @covers  ::getDirectoryMatch()
     * @covers  ::getFileTypes()
     */
    public function testCreate()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->exactly(4))
            ->method('getHomeUrl')
            ->will($this->returnValue('http://www.test.com/path'));

        $wordpress->expects($this->exactly(7))
            ->method('gotModRewrite')
            ->will($this->onConsecutiveCalls(false, false, false, true, true, true, true));

        /**
         * @var \stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->user_login = 'userLogin';
        $user->user_pass = 'userPass';

        $wordpress->expects($this->exactly(3))
            ->method('getCurrentUser')
            ->will($this->returnValue($user));

        $wordpressConfig = $this->getWordpressConfig();

        $wordpressConfig->expects($this->exactly(3))
            ->method('getMimeTypes')
            ->will($this->returnValue(['jpg' => 'firstType']));

        $mainConfig = $this->getMainConfig();

        $mainConfig->expects($this->exactly(7))
            ->method('getLockedFileType')
            ->will($this->onConsecutiveCalls(null, 'selected', 'not_selected', null, 'selected', null, null));

        $mainConfig->expects($this->exactly(2))
            ->method('getLockedFiles')
            ->will($this->returnValue('png,jpg'));

        $mainConfig->expects($this->once())
            ->method('getNotLockedFiles')
            ->will($this->returnValue('png,jpg'));

        $mainConfig->expects($this->exactly(3))
            ->method('getFilePassType')
            ->will($this->returnValue(null));

        $mainConfig->expects($this->exactly(8))
            ->method('getLockedDirectoryType')
            ->will($this->onConsecutiveCalls(
                'wordpress',
                'wordpress',
                'custom',
                'custom',
                'all',
                'all',
                'all',
                'all'
            ));

        $mainConfig->expects($this->once())
            ->method('getCustomLockedDirectories')
            ->will($this->returnValue('customLockedDirectories'));

        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('testDir', new Directory());
        $testDir = 'vfs://testDir';

        $apacheFileProtection = new ApacheFileProtection(
            $this->getPhp(),
            $wordpress,
            $wordpressConfig,
            $mainConfig,
            $this->getUtil()
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
            ."RewriteEngine On\n"
            ."RewriteBase /path/\n"
            ."RewriteRule ^index\.php$ - [L]\n"
            ."RewriteCond %{REQUEST_URI} ^.*/[0-9]{4}/[0-9]{2}.*$\n"
            ."RewriteRule ^([^?]*)$ /path/index.php?uamfiletype=attachment&uamgetfile=$1 [QSA,L]\n"
            ."RewriteRule ^(.*)\\?(((?!uamfiletype).)*)$ "
                ."/path/index.php?uamfiletype=attachment&uamgetfile=$1&$2 [QSA,L]\n"
            ."RewriteRule ^(.*)\\?(.*)$ /path/index.php?uamgetfile=$1&$2 [QSA,L]\n"
            ."</IfModule>\n",
            file_get_contents($file)
        );

        self::assertTrue($apacheFileProtection->create($testDir, 'objectType'));
        self::assertEquals(
            "<IfModule mod_rewrite.c>\n"
            ."<FilesMatch '\.(jpg)'>\n"
            ."RewriteEngine On\n"
            ."RewriteBase /path/\n"
            ."RewriteRule ^index\.php$ - [L]\n"
            ."RewriteCond %{REQUEST_URI} customLockedDirectories\n"
            ."RewriteRule ^([^?]*)$ /path/index.php?uamfiletype=objectType&uamgetfile=$1 [QSA,L]\n"
            ."RewriteRule ^(.*)\\?(((?!uamfiletype).)*)$ "
                ."/path/index.php?uamfiletype=objectType&uamgetfile=$1&$2 [QSA,L]\n"
            ."RewriteRule ^(.*)\\?(.*)$ /path/index.php?uamgetfile=$1&$2 [QSA,L]\n"
            ."</FilesMatch>\n"
            ."</IfModule>\n",
            file_get_contents($file)
        );

        self::assertTrue($apacheFileProtection->create($testDir, 'objectType'));
        self::assertEquals(
            "<IfModule mod_rewrite.c>\n"
            ."RewriteEngine On\n"
            ."RewriteBase /path/\n"
            ."RewriteRule ^index\.php$ - [L]\n"
            ."RewriteRule ^([^?]*)$ /path/index.php?uamfiletype=objectType&uamgetfile=$1 [QSA,L]\n"
            ."RewriteRule ^(.*)\\?(((?!uamfiletype).)*)$ "
            ."/path/index.php?uamfiletype=objectType&uamgetfile=$1&$2 [QSA,L]\n"
            ."RewriteRule ^(.*)\\?(.*)$ /path/index.php?uamgetfile=$1&$2 [QSA,L]\n"
            ."</IfModule>\n",
            file_get_contents($file)
        );

        self::assertFalse($apacheFileProtection->create('invalid', 'invalid'));
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
                ['vfs://testDir/.htaccess'],
                ['vfs://testDir/.htpasswd'],
                ['vfs://testDir/.htaccess'],
                ['vfs://testDir/.htpasswd'],
                ['vfs://testDir/.htaccess'],
                ['vfs://testDir/.htpasswd']
            )
            ->will($this->onConsecutiveCalls(true, true, true, false, false, true));

        $apacheFileProtection = new ApacheFileProtection(
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
