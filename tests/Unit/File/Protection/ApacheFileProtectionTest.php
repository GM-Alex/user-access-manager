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

namespace UserAccessManager\Tests\Unit\File\Protection;

use stdClass;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\File\Protection\ApacheFileProtection;
use UserAccessManager\Tests\ThrowingStreamWrapper;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class ApacheFileProtectionTest
 *
 * @package UserAccessManager\Tests\Unit\File
 * @coversDefaultClass \UserAccessManager\File\Protection\ApacheFileProtection
 */
class ApacheFileProtectionTest extends UserAccessManagerTestCase
{
    private FileSystem $root;

    protected function setUp(): void
    {
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();
    }

    protected function tearDown(): void
    {
        $this->root->unmount();
    }

    private function createApacheFileProtection(
        ?Php $php = null,
        ?Wordpress $wordpress = null,
        ?WordpressConfig $wordpressConfig = null,
        ?MainConfig $mainConfig = null,
        ?Util $util = null
    ): ApacheFileProtection {
        return new ApacheFileProtection(
            $php ?? $this->getPhp(),
            $wordpress ?? $this->getWordpress(),
            $wordpressConfig ?? $this->getWordpressConfig(),
            $mainConfig ?? $this->getMainConfig(),
            $util ?? $this->getUtil()
        );
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
        $apacheFileProtection = $this->createApacheFileProtection();

        self::assertEquals(ApacheFileProtection::FILE_NAME, $apacheFileProtection->getFileNameWithPath());
        self::assertEquals(
            'dir/' . ApacheFileProtection::FILE_NAME,
            $apacheFileProtection->getFileNameWithPath('dir/')
        );
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
            ->method('getSiteUrl')
            ->will($this->returnValue('http://www.test.com/path'));

        $wordpress->expects($this->exactly(7))
            ->method('gotModRewrite')
            ->will($this->onConsecutiveCalls(false, false, false, true, true, true, true));

        /**
         * @var stdClass $user
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
        $rootDir->add('secondTestDir', new Directory([
            'sites' => new Directory([
                '2' => new Directory()
            ])
        ]));
        $testDir = 'vfs://testDir';
        $secondTestDir = 'vfs://secondTestDir/sites/2/';

        $apacheFileProtection = $this->createApacheFileProtection(
            wordpress: $wordpress,
            wordpressConfig: $wordpressConfig,
            mainConfig: $mainConfig
        );

        $file = $testDir . '/' . ApacheFileProtection::FILE_NAME;
        $passwordFile = $testDir . '/' . ApacheFileProtection::PASSWORD_FILE_NAME;

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
            . "AuthType Basic\nAuthName \"WP-Files\"\n"
            . "AuthUserFile vfs://testDir/.htpasswd\nrequire valid-user\n"
            . "</FilesMatch>\n",
            file_get_contents($file)
        );

        self::assertTrue($apacheFileProtection->create($testDir));
        self::assertEquals(
            "<FilesMatch '^\.(jpg)'>"
            . "\nAuthType Basic\nAuthName \"WP-Files\"\n"
            . "AuthUserFile vfs://testDir/.htpasswd\nrequire valid-user\n"
            . "</FilesMatch>\n",
            file_get_contents($file)
        );

        self::assertTrue($apacheFileProtection->create($testDir));
        self::assertEquals(
            "<IfModule mod_rewrite.c>\n"
            . "RewriteEngine On\n"
            . "RewriteBase /path/\n"
            . "RewriteRule ^index\.php$ - [L]\n"
            . "RewriteCond %{REQUEST_URI} !.*\/sites\/[0-9]+\/.*\n"
            . "RewriteCond %{REQUEST_URI} ^.*/\d{4}/\d{2}.*$\n"
            . "RewriteRule ^([^?]*)$ /path/index.php?uamfiletype=attachment&uamgetfile=$1 [QSA,L]\n"
            . "RewriteRule ^(.*)\\?(((?!uamfiletype).)*)$ "
            . "/path/index.php?uamfiletype=attachment&uamgetfile=$1&$2 [QSA,L]\n"
            . "RewriteRule ^(.*)\\?(.*)$ /path/index.php?uamgetfile=$1&$2 [QSA,L]\n"
            . "</IfModule>\n",
            file_get_contents($file)
        );

        self::assertTrue($apacheFileProtection->create($testDir, 'objectType'));
        self::assertEquals(
            "<IfModule mod_rewrite.c>\n"
            . "<FilesMatch '\.(jpg)'>\n"
            . "RewriteEngine On\n"
            . "RewriteBase /path/\n"
            . "RewriteRule ^index\.php$ - [L]\n"
            . "RewriteCond %{REQUEST_URI} !.*\/sites\/[0-9]+\/.*\n"
            . "RewriteCond %{REQUEST_URI} customLockedDirectories\n"
            . "RewriteRule ^([^?]*)$ /path/index.php?uamfiletype=objectType&uamgetfile=$1 [QSA,L]\n"
            . "RewriteRule ^(.*)\\?(((?!uamfiletype).)*)$ "
            . "/path/index.php?uamfiletype=objectType&uamgetfile=$1&$2 [QSA,L]\n"
            . "RewriteRule ^(.*)\\?(.*)$ /path/index.php?uamgetfile=$1&$2 [QSA,L]\n"
            . "</FilesMatch>\n"
            . "</IfModule>\n",
            file_get_contents($file)
        );

        $secondFile = $secondTestDir . '/' . ApacheFileProtection::FILE_NAME;

        self::assertTrue($apacheFileProtection->create($secondTestDir, 'objectType'));
        self::assertEquals(
            "<IfModule mod_rewrite.c>\n"
            . "RewriteEngine On\n"
            . "RewriteBase /path/\n"
            . "RewriteRule ^index\.php$ - [L]\n"
            . "RewriteRule ^([^?]*)$ /path/index.php?uamfiletype=objectType&uamgetfile=$1 [QSA,L]\n"
            . "RewriteRule ^(.*)\\?(((?!uamfiletype).)*)$ "
            . "/path/index.php?uamfiletype=objectType&uamgetfile=$1&$2 [QSA,L]\n"
            . "RewriteRule ^(.*)\\?(.*)$ /path/index.php?uamgetfile=$1&$2 [QSA,L]\n"
            . "</IfModule>\n",
            file_get_contents($secondFile)
        );

        self::assertFalse($apacheFileProtection->create('invalid', 'invalid'));
    }

    /**
     * @group  unit
     * @covers ::create()
     * @covers ::getPermalinkFileContent()
     */
    public function testCreateForDirectoryContainingButNotEndingInSites()
    {
        $wordpress = $this->getWordpress();
        $wordpress->method('getSiteUrl')->will($this->returnValue('http://www.test.com/path'));
        $wordpress->method('gotModRewrite')->will($this->returnValue(true));

        $mainConfig = $this->getMainConfig();
        $mainConfig->method('getLockedDirectoryType')->will($this->returnValue('all'));
        $mainConfig->method('getLockedFileType')->will($this->returnValue(null));

        $rootDir = $this->root->get('/');
        $rootDir->add('base', new Directory([
            'sites' => new Directory([
                '2' => new Directory([
                    'deep' => new Directory()
                ])
            ])
        ]));

        $apacheFileProtection = $this->createApacheFileProtection(wordpress: $wordpress, mainConfig: $mainConfig);

        // The directory contains "/sites/2/" but does not end with it, so it is not a sub site.
        self::assertTrue($apacheFileProtection->create('vfs://base/sites/2/deep/', 'objectType'));
        self::assertStringContainsString(
            'RewriteCond %{REQUEST_URI} !.*\/sites\/[0-9]+\/.*',
            file_get_contents('vfs://base/sites/2/deep/' . ApacheFileProtection::FILE_NAME)
        );
    }

    /**
     * @group  unit
     * @covers ::create()
     */
    public function testCreateReturnsFalseWhenWritingThrows()
    {
        ThrowingStreamWrapper::register();

        try {
            $wordpress = $this->getWordpress();
            $wordpress->method('getSiteUrl')->will($this->returnValue('http://www.test.com/path'));
            $wordpress->method('gotModRewrite')->will($this->returnValue(true));

            $mainConfig = $this->getMainConfig();
            $mainConfig->method('getLockedDirectoryType')->will($this->returnValue('all'));
            $mainConfig->method('getLockedFileType')->will($this->returnValue(null));

            $apacheFileProtection = $this->createApacheFileProtection(
                wordpress: $wordpress,
                mainConfig: $mainConfig
            );

            self::assertFalse(
                $apacheFileProtection->create(ThrowingStreamWrapper::PROTOCOL . '://dir/', 'objectType')
            );
        } finally {
            ThrowingStreamWrapper::unregister();
        }
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

        $apacheFileProtection = $this->createApacheFileProtection($php);

        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('testDir', new Directory([
            ApacheFileProtection::FILE_NAME => new File('htaccess'),
            ApacheFileProtection::PASSWORD_FILE_NAME => new File('password')
        ]));

        $testDir = 'vfs://testDir/';
        $file = $testDir . ApacheFileProtection::FILE_NAME;
        $passwordFile = $testDir . ApacheFileProtection::PASSWORD_FILE_NAME;

        self::assertTrue(file_exists($file));
        self::assertTrue(file_exists($passwordFile));
        self::assertTrue($apacheFileProtection->delete($testDir));
        self::assertFalse($apacheFileProtection->delete($testDir));
        self::assertFalse($apacheFileProtection->delete($testDir));
    }
}
