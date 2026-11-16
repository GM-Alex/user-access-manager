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
    private const OWN_BLOCK = "# BEGIN User Access Manager\nRewriteRule ^a$ b [L]\n# END User Access Manager\n";
    private const FOREIGN_BLOCK = "# BEGIN Other Plugin\nRewriteRule ^x$ y [L]\n# END Other Plugin\n";
    private const REWRITE_GUARD = "# BEGIN User Access Manager\n"
        . "<IfModule mod_rewrite.c>\nRewriteEngine On\n</IfModule>\n"
        . "# END User Access Manager\n";

    private FileSystem $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();
    }

    protected function tearDown(): void
    {
        $this->root->unmount();
        parent::tearDown();
    }

    private function getOwnBlock(string $file): string
    {
        $content = (string) file_get_contents($file);
        $start = "# BEGIN User Access Manager\n";
        $end = "# END User Access Manager\n";

        self::assertStringStartsWith($start, $content);
        self::assertStringEndsWith($end, $content);

        $endPosition = (int) strpos($content, $end);

        return substr($content, strlen($start), $endPosition - strlen($start));
    }

    private function addTestDirectory(string $fileContent, bool $withPasswordFile = false): void
    {
        $files = [ApacheFileProtection::FILE_NAME => new File($fileContent)];

        if ($withPasswordFile === true) {
            $files[ApacheFileProtection::PASSWORD_FILE_NAME] = new File('password');
        }

        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('testDir', new Directory($files));
    }

    private function createApacheFileProtectionWithPermalinks(): ApacheFileProtection
    {
        $wordpress = $this->getWordpress();
        $wordpress->method('getSiteUrl')->will($this->returnValue('http://www.test.com/path'));
        $wordpress->method('gotModRewrite')->will($this->returnValue(true));

        $mainConfig = $this->getMainConfig();
        $mainConfig->method('getLockedFileType')->will($this->returnValue('all'));
        $mainConfig->method('getLockedDirectoryType')->will($this->returnValue('all'));

        return $this->createApacheFileProtection(wordpress: $wordpress, mainConfig: $mainConfig);
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
            $this->getOwnBlock($file)
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
            $this->getOwnBlock($file)
        );

        self::assertTrue($apacheFileProtection->create($testDir));
        self::assertEquals(
            "<FilesMatch '^\.(jpg)'>"
            . "\nAuthType Basic\nAuthName \"WP-Files\"\n"
            . "AuthUserFile vfs://testDir/.htpasswd\nrequire valid-user\n"
            . "</FilesMatch>\n",
            $this->getOwnBlock($file)
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
            $this->getOwnBlock($file)
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
            $this->getOwnBlock($file)
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
            $this->getOwnBlock($secondFile)
        );

        self::assertFalse($apacheFileProtection->create('invalid', 'invalid'));
    }

    /**
     * @group  unit
     * @covers ::create()
     * @covers ::getMarkedBlock()
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
     * @covers ::getMarkedBlock()
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
        // Content of a version that wrote the whole file, so the file is the own one.
        $rootDir->add('testDir', new Directory([
            ApacheFileProtection::FILE_NAME => new File('RewriteRule ^([^?]*)$ /index.php?uamgetfile=$1 [QSA,L]'),
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

    /**
     * @group  unit
     * @covers ::delete()
     * @covers ::getForeignContent()
     * @covers ::isOwnLegacyContent()
     */
    public function testDeleteKeepsForeignContent()
    {
        $php = $this->getPhp();
        $php->expects($this->once())
            ->method('filePutContents')
            ->with('vfs://testDir/.htaccess', self::FOREIGN_BLOCK)
            ->will($this->returnValue(strlen(self::FOREIGN_BLOCK)));

        $php->expects($this->once())
            ->method('unlink')
            ->with('vfs://testDir/.htpasswd')
            ->will($this->returnValue(true));

        $this->addTestDirectory(self::OWN_BLOCK . self::FOREIGN_BLOCK, true);

        self::assertTrue($this->createApacheFileProtection($php)->delete('vfs://testDir/'));
    }

    /**
     * @group  unit
     * @covers ::delete()
     * @covers ::getForeignContent()
     * @covers ::isOwnLegacyContent()
     */
    public function testDeleteKeepsAFileWithoutOwnContent()
    {
        $foreignContent = "RewriteRule ^foo$ bar [L]\n";

        $php = $this->getPhp();
        $php->expects($this->once())
            ->method('filePutContents')
            ->with('vfs://testDir/.htaccess', $foreignContent)
            ->will($this->returnValue(false));

        $php->expects($this->never())
            ->method('unlink');

        $this->addTestDirectory($foreignContent);

        self::assertFalse($this->createApacheFileProtection($php)->delete('vfs://testDir/'));
    }

    /**
     * @group  unit
     * @covers ::create()
     * @covers ::getMarkedBlock()
     * @covers ::getForeignContent()
     * @covers ::isOwnLegacyContent()
     */
    public function testCreateKeepsForeignContentBehindTheOwnBlock()
    {
        $this->addTestDirectory(self::FOREIGN_BLOCK);
        $apacheFileProtection = $this->createApacheFileProtectionWithPermalinks();
        $file = 'vfs://testDir/' . ApacheFileProtection::FILE_NAME;

        self::assertTrue($apacheFileProtection->create('vfs://testDir'));

        $content = (string) file_get_contents($file);
        self::assertStringStartsWith("# BEGIN User Access Manager\n", $content);
        self::assertStringEndsWith(self::FOREIGN_BLOCK . self::REWRITE_GUARD, $content);
        self::assertStringContainsString('uamgetfile', $content);
        // The own rules end in [L] and have to be reached before the foreign ones.
        self::assertLessThan(
            strpos($content, '# BEGIN Other Plugin'),
            strpos($content, '# END User Access Manager')
        );

        self::assertTrue($apacheFileProtection->create('vfs://testDir'));
        // The rules and the guard, not a third one stacked on top.
        self::assertSame(2, substr_count((string) file_get_contents($file), '# BEGIN User Access Manager'));
        self::assertStringEndsWith(
            self::FOREIGN_BLOCK . self::REWRITE_GUARD,
            (string) file_get_contents($file)
        );
    }

    /**
     * @group  unit
     * @covers ::create()
     * @covers ::getMarkedBlock()
     * @covers ::getForeignContent()
     */
    public function testCreateKeepsForeignContentFromBothSidesOfTheOwnBlock()
    {
        $this->addTestDirectory("# BEFORE\n" . self::OWN_BLOCK . "\n# AFTER\n");

        self::assertTrue($this->createApacheFileProtectionWithPermalinks()->create('vfs://testDir'));

        $content = (string) file_get_contents('vfs://testDir/' . ApacheFileProtection::FILE_NAME);

        self::assertStringStartsWith("# BEGIN User Access Manager\n", $content);
        // Both sides are kept, and the blank line behind the old block is dropped.
        self::assertStringEndsWith("# BEFORE\n# AFTER\n" . self::REWRITE_GUARD, $content);
        self::assertSame(2, substr_count($content, '# BEGIN User Access Manager'));
        self::assertStringNotContainsString('RewriteRule ^a$ b [L]', $content);
    }

    /**
     * @group  unit
     * @covers ::create()
     * @covers ::getMarkedBlock()
     * @covers ::getForeignContent()
     */
    public function testCreateDropsATruncatedOwnBlock()
    {
        $this->addTestDirectory("# KEPT\n# BEGIN User Access Manager\nRewriteRule ^cut$ off [L]\n");

        self::assertTrue($this->createApacheFileProtectionWithPermalinks()->create('vfs://testDir'));

        $content = (string) file_get_contents('vfs://testDir/' . ApacheFileProtection::FILE_NAME);

        self::assertStringEndsWith("# KEPT\n" . self::REWRITE_GUARD, $content);
        self::assertStringNotContainsString('RewriteRule ^cut$ off [L]', $content);
    }

    /**
     * @group  unit
     * @covers ::delete()
     * @covers ::getForeignContent()
     */
    public function testDeleteRemovesAFileLeftWithWhitespaceOnly()
    {
        $php = $this->getPhp();
        $php->expects($this->never())
            ->method('filePutContents');

        $php->expects($this->exactly(2))
            ->method('unlink')
            ->withConsecutive(['vfs://testDir/.htaccess'], ['vfs://testDir/.htpasswd'])
            ->will($this->returnValue(true));

        // Whitespace left behind the removed block does not count as foreign content.
        $this->addTestDirectory(self::OWN_BLOCK . "\n  \n", true);

        self::assertTrue($this->createApacheFileProtection($php)->delete('vfs://testDir/'));
    }

    /**
     * @group  unit
     * @covers ::delete()
     */
    public function testDeleteFailsWhenThePasswordFileRemains()
    {
        $php = $this->getPhp();
        $php->expects($this->once())
            ->method('filePutContents')
            ->will($this->returnValue(strlen(self::FOREIGN_BLOCK)));

        $php->expects($this->once())
            ->method('unlink')
            ->with('vfs://testDir/.htpasswd')
            ->will($this->returnValue(false));

        $this->addTestDirectory(self::OWN_BLOCK . self::FOREIGN_BLOCK, true);

        self::assertFalse($this->createApacheFileProtection($php)->delete('vfs://testDir/'));
    }

    /**
     * Without mod_rewrite the protection rests on "require valid-user". A foreign
     * "Require all granted" would join it in an implicit RequireAny and grant access,
     * so no foreign content is carried over in that mode.
     *
     * @group  unit
     * @covers ::create()
     * @covers ::getMarkedBlock()
     */
    public function testCreateDropsForeignContentWithoutModRewrite()
    {
        $wordpress = $this->getWordpress();
        $wordpress->method('gotModRewrite')->will($this->returnValue(false));

        $mainConfig = $this->getMainConfig();
        $mainConfig->method('getLockedFileType')->will($this->returnValue('all'));

        $this->addTestDirectory("Require all granted\n" . self::FOREIGN_BLOCK);

        $apacheFileProtection = $this->createApacheFileProtection(
            wordpress: $wordpress,
            mainConfig: $mainConfig
        );

        self::assertTrue($apacheFileProtection->create('vfs://testDir'));

        $content = (string) file_get_contents('vfs://testDir/' . ApacheFileProtection::FILE_NAME);

        self::assertStringNotContainsString('Require all granted', $content);
        self::assertStringNotContainsString('# BEGIN Other Plugin', $content);
        self::assertStringEndsWith("# END User Access Manager\n", $content);
        self::assertStringContainsString('require valid-user', $content);
    }

    /**
     * @group  unit
     * @covers ::create()
     * @covers ::getMarkedBlock()
     * @covers ::getForeignContent()
     * @covers ::isOwnLegacyContent()
     */
    public function testCreateReplacesContentWrittenWithoutMarkers()
    {
        $this->addTestDirectory(
            "# legacy own rules\n"
            . "<IfModule mod_rewrite.c>\n"
            . "RewriteRule ^([^?]*)$ /path/index.php?uamfiletype=attachment&uamgetfile=$1 [QSA,L]\n"
            . "</IfModule>\n"
        );

        self::assertTrue($this->createApacheFileProtectionWithPermalinks()->create('vfs://testDir'));

        $content = (string) file_get_contents('vfs://testDir/' . ApacheFileProtection::FILE_NAME);
        self::assertStringEndsWith(self::REWRITE_GUARD, $content);
        // Replaced, not carried along as foreign content.
        self::assertStringNotContainsString('# legacy own rules', $content);
        self::assertSame(2, substr_count($content, '<IfModule mod_rewrite.c>'));
    }

    /**
     * @group  unit
     * @covers ::create()
     * @covers ::getMarkedBlock()
     * @covers ::getForeignContent()
     * @covers ::isOwnLegacyContent()
     */
    public function testCreateReplacesContentWrittenWithoutMarkersAndWithoutModRewrite()
    {
        $this->addTestDirectory(
            "AuthType Basic\nAuthName \"WP-Files\"\n"
            . "AuthUserFile vfs://testDir/.htpasswd\nrequire valid-user\n"
        );

        /**
         * @var stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->user_login = 'userLogin';
        $user->user_pass = 'userPass';

        $wordpress = $this->getWordpress();
        $wordpress->method('gotModRewrite')->will($this->returnValue(false));
        $wordpress->method('getCurrentUser')->will($this->returnValue($user));

        $mainConfig = $this->getMainConfig();
        $mainConfig->method('getLockedFileType')->will($this->returnValue('all'));

        $apacheFileProtection = $this->createApacheFileProtection(
            wordpress: $wordpress,
            mainConfig: $mainConfig
        );

        self::assertTrue($apacheFileProtection->create('vfs://testDir'));

        $content = (string) file_get_contents('vfs://testDir/' . ApacheFileProtection::FILE_NAME);
        self::assertStringEndsWith("# END User Access Manager\n", $content);
        self::assertSame(1, substr_count($content, 'AuthName "WP-Files"'));
    }
}
