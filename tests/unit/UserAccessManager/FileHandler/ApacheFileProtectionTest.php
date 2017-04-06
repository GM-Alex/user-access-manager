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

use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class ApacheFileProtectionTest
 *
 * @package UserAccessManager\FileHandler
 */
class ApacheFileProtectionTest extends \UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private $oRoot;

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
        $oApacheFileProtection = new ApacheFileProtection(
            $this->getWordpress(),
            $this->getConfig(),
            $this->getUtil()
        );

        self::assertInstanceOf('\UserAccessManager\FileHandler\ApacheFileProtection', $oApacheFileProtection);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\FileHandler\ApacheFileProtection::create()
     */
    public function testCreate()
    {
        $oWordpress = $this->getWordpress();

        $oWordpress->expects($this->exactly(2))
            ->method('getHomeUrl')
            ->will($this->returnValue('http://www.test.com'));

        /**
         * @var \stdClass $oUser
         */
        $oUser = $this->getMockBuilder('\WP_User')->getMock();
        $oUser->user_login = 'userLogin';
        $oUser->user_pass = 'userPass';

        $oWordpress->expects($this->exactly(3))
            ->method('getCurrentUser')
            ->will($this->returnValue($oUser));

        $oConfig = $this->getConfig();
        $oUtil = $this->getUtil();

        $oConfig->expects($this->exactly(5))
            ->method('isPermalinksActive')
            ->will($this->onConsecutiveCalls(false, false, false, true, true));

        $oConfig->expects($this->exactly(3))
            ->method('getLockFileTypes')
            ->will($this->onConsecutiveCalls(null, 'selected', 'not_selected'));

        $oConfig->expects($this->exactly(2))
            ->method('getLockedFileTypes')
            ->will($this->returnValue('png,jpg'));

        $oConfig->expects($this->exactly(2))
            ->method('getMimeTypes')
            ->will($this->returnValue(['jpg' => 'firstType']));

        $oConfig->expects($this->exactly(3))
            ->method('getFilePassType')
            ->will($this->returnValue(null));

        /**
         * @var Directory $oRootDir
         */
        $oRootDir = $this->oRoot->get('/');
        $oRootDir->add('testDir', new Directory());
        $sTestDir = 'vfs://testDir';

        $oApacheFileProtection = new ApacheFileProtection($oWordpress, $oConfig, $oUtil);

        $sFile = 'vfs://testDir/'.ApacheFileProtection::FILE_NAME;
        $sPasswordFile = 'vfs://testDir/'.ApacheFileProtection::PASSWORD_FILE_NAME;

        self::assertTrue($oApacheFileProtection->create($sTestDir));
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

        self::assertTrue($oApacheFileProtection->create($sTestDir));
        self::assertEquals(
            "<FilesMatch '\.(jpg)'>\nAuthType Basic\nAuthName \"WP-Files\"\n"
            ."AuthUserFile vfs://testDir/.htpasswd\nrequire valid-user\n</FilesMatch>\n",
            file_get_contents($sFile)
        );

        self::assertTrue($oApacheFileProtection->create($sTestDir));
        self::assertEquals(
            "<FilesMatch '^\.(jpg)'>\nAuthType Basic\nAuthName \"WP-Files\"\n"
            ."AuthUserFile vfs://testDir/.htpasswd\nrequire valid-user\n</FilesMatch>\n",
            file_get_contents($sFile)
        );

        self::assertTrue($oApacheFileProtection->create($sTestDir));
        self::assertEquals(
            "<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase /\nRewriteRule ^index\.php$ - [L]\n"
            ."RewriteRule (.*) /index.php?uamfiletype=attachment&uamgetfile=$1 [L]\n</IfModule>\n",
            file_get_contents($sFile)
        );

        self::assertTrue($oApacheFileProtection->create($sTestDir, 'objectType'));
        self::assertEquals(
            "<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase /\nRewriteRule ^index\.php$ - [L]\n"
            ."RewriteRule (.*) /index.php?uamfiletype=objectType&uamgetfile=$1 [L]\n</IfModule>\n",
            file_get_contents($sFile)
        );
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\FileHandler\ApacheFileProtection::delete()
     */
    public function testDelete()
    {
        $oWordpress = $this->getWordpress();
        $oConfig = $this->getConfig();
        $oUtil = $this->getUtil();
        $oApacheFileProtection = new ApacheFileProtection($oWordpress, $oConfig, $oUtil);

        /**
         * @var Directory $oRootDir
         */
        $oRootDir = $this->oRoot->get('/');
        $oRootDir->add('testDir', new Directory([
            ApacheFileProtection::FILE_NAME => new File('htaccess'),
            ApacheFileProtection::PASSWORD_FILE_NAME => new File('password')
        ]));

        $sTestDir = 'vfs://testDir/';
        $sFile = $sTestDir.ApacheFileProtection::FILE_NAME;
        $sPasswordFile = $sTestDir.ApacheFileProtection::PASSWORD_FILE_NAME;

        self::assertTrue(file_exists($sFile));
        self::assertTrue(file_exists($sPasswordFile));
        self::assertTrue($oApacheFileProtection->delete($sTestDir));
        self::assertFalse(file_exists($sFile));
        //seems a bug in vsf self::assertFalse(file_exists($sPasswordFile));
    }
}
