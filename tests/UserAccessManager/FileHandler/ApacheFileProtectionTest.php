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
 * @version   SVN: $Id$
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
     * @group  unit
     * @covers \UserAccessManager\FileHandler\ApacheFileProtection::__construct()
     */
    public function testCanCreateInstance()
    {
        $ApacheFileProtection = new ApacheFileProtection(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getUtil()
        );

        self::assertInstanceOf('\UserAccessManager\FileHandler\ApacheFileProtection', $ApacheFileProtection);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\FileHandler\ApacheFileProtection::create()
     */
    public function testCreate()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->exactly(3))
            ->method('getHomeUrl')
            ->will($this->returnValue('http://www.test.com'));

        /**
         * @var \stdClass $User
         */
        $User = $this->getMockBuilder('\WP_User')->getMock();
        $User->user_login = 'userLogin';
        $User->user_pass = 'userPass';

        $Wordpress->expects($this->exactly(3))
            ->method('getCurrentUser')
            ->will($this->returnValue($User));

        $Config = $this->getConfig();
        $Util = $this->getUtil();

        $Config->expects($this->exactly(6))
            ->method('isPermalinksActive')
            ->will($this->onConsecutiveCalls(false, false, false, true, true, true));

        $Config->expects($this->exactly(3))
            ->method('getLockFileTypes')
            ->will($this->onConsecutiveCalls(null, 'selected', 'not_selected'));

        $Config->expects($this->exactly(2))
            ->method('getLockedFileTypes')
            ->will($this->returnValue('png,jpg'));

        $Config->expects($this->exactly(2))
            ->method('getMimeTypes')
            ->will($this->returnValue(['jpg' => 'firstType']));

        $Config->expects($this->exactly(3))
            ->method('getFilePassType')
            ->will($this->returnValue(null));

        /**
         * @var Directory $RootDir
         */
        $RootDir = $this->oRoot->get('/');
        $RootDir->add('testDir', new Directory());
        $sTestDir = 'vfs://testDir';

        $ApacheFileProtection = new ApacheFileProtection(
            $this->getPhp(),
            $Wordpress,
            $Config,
            $Util
        );

        $sFile = 'vfs://testDir/'.ApacheFileProtection::FILE_NAME;
        $sPasswordFile = 'vfs://testDir/'.ApacheFileProtection::PASSWORD_FILE_NAME;

        self::assertTrue($ApacheFileProtection->create($sTestDir));
        self::assertTrue(file_exists($sFile));
        self::assertTrue(file_exists($sPasswordFile));
        self::assertEquals(
            "AuthType Basic\nAuthName \"WP-Files\"\nAuthUserFile vfs://testDir/.htpasswd\nrequire valid-user\n",
            file_get_contents($sFile)
        );
        self::assertEquals(
            "userLogin:userPass\n",
            file_get_contents($sPasswordFile)
        );

        self::assertTrue($ApacheFileProtection->create($sTestDir));
        self::assertEquals(
            "<FilesMatch '\.(jpg)'>\nAuthType Basic\nAuthName \"WP-Files\"\n"
            ."AuthUserFile vfs://testDir/.htpasswd\nrequire valid-user\n</FilesMatch>\n",
            file_get_contents($sFile)
        );

        self::assertTrue($ApacheFileProtection->create($sTestDir));
        self::assertEquals(
            "<FilesMatch '^\.(jpg)'>\nAuthType Basic\nAuthName \"WP-Files\"\n"
            ."AuthUserFile vfs://testDir/.htpasswd\nrequire valid-user\n</FilesMatch>\n",
            file_get_contents($sFile)
        );

        self::assertTrue($ApacheFileProtection->create($sTestDir));
        self::assertEquals(
            "<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase /\nRewriteRule ^index\.php$ - [L]\n"
            ."RewriteRule (.*) /index.php?uamfiletype=attachment&uamgetfile=$1 [L]\n</IfModule>\n",
            file_get_contents($sFile)
        );

        self::assertTrue($ApacheFileProtection->create($sTestDir, 'objectType'));
        self::assertEquals(
            "<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase /\nRewriteRule ^index\.php$ - [L]\n"
            ."RewriteRule (.*) /index.php?uamfiletype=objectType&uamgetfile=$1 [L]\n</IfModule>\n",
            file_get_contents($sFile)
        );

        self::assertFalse($ApacheFileProtection->create('invalid', 'invalid'));
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\FileHandler\ApacheFileProtection::delete()
     */
    public function testDelete()
    {
        $Php = $this->getPhp();
        $Php->expects($this->exactly(6))
            ->method('unlink')
            ->will($this->onConsecutiveCalls(true, true, true, false, false, true));

        $ApacheFileProtection = new ApacheFileProtection(
            $Php,
            $this->getWordpress(),
            $this->getConfig(),
            $this->getUtil()
        );

        /**
         * @var Directory $RootDir
         */
        $RootDir = $this->oRoot->get('/');
        $RootDir->add('testDir', new Directory([
            ApacheFileProtection::FILE_NAME => new File('htaccess'),
            ApacheFileProtection::PASSWORD_FILE_NAME => new File('password')
        ]));

        $sTestDir = 'vfs://testDir/';
        $sFile = $sTestDir.ApacheFileProtection::FILE_NAME;
        $sPasswordFile = $sTestDir.ApacheFileProtection::PASSWORD_FILE_NAME;

        self::assertTrue(file_exists($sFile));
        self::assertTrue(file_exists($sPasswordFile));
        self::assertTrue($ApacheFileProtection->delete($sTestDir));
        self::assertFalse($ApacheFileProtection->delete($sTestDir));
        self::assertFalse($ApacheFileProtection->delete($sTestDir));
    }
}
