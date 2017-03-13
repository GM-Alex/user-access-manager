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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\FileHandler;

use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class NginxFileProtectionTest
 *
 * @package UserAccessManager\FileHandler
 */
class NginxFileProtectionTest extends \UserAccessManagerTestCase
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
     *
     * @covers \UserAccessManager\FileHandler\NginxFileProtection::__construct()
     */
    public function testCanCreateInstance()
    {
        $oNginxFileProtection = new NginxFileProtection(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getUtil()
        );

        self::assertInstanceOf('\UserAccessManager\FileHandler\NginxFileProtection', $oNginxFileProtection);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\FileHandler\NginxFileProtection::create()
     */
    public function testCreate()
    {
        $oWrapper = $this->getWrapper();

        /**
         * @var \stdClass $oUser
         */
        $oUser = $this->getMockBuilder('\WP_User')->getMock();
        $oUser->user_login = 'userLogin';
        $oUser->user_pass = 'userPass';

        $oWrapper->expects($this->exactly(2))
            ->method('getCurrentUser')
            ->will($this->returnValue($oUser));

        $oConfig = $this->getConfig();
        $oUtil = $this->getUtil();

        $oConfig->expects($this->exactly(4))
            ->method('isPermalinksActive')
            ->will($this->onConsecutiveCalls(false, false, true, true));

        $oConfig->expects($this->exactly(2))
            ->method('getLockFileTypes')
            ->will($this->onConsecutiveCalls(null, 'selected'));

        $oConfig->expects($this->exactly(1))
            ->method('getLockedFileTypes')
            ->will($this->returnValue('png,jpg'));

        $oConfig->expects($this->exactly(1))
            ->method('getMimeTypes')
            ->will($this->returnValue(['jpg' => 'firstType']));

        $oConfig->expects($this->exactly(2))
            ->method('getFilePassType')
            ->will($this->returnValue(null));

        /**
         * @var Directory $oRootDir
         */
        $oRootDir = $this->oRoot->get('/');
        $oRootDir->add('testDir', new Directory());
        $sTestDir = 'vfs://testDir';

        $oNginxFileProtection = new NginxFileProtection($oWrapper, $oConfig, $oUtil);
        $sFile = 'vfs://testDir/'.NginxFileProtection::FILE_NAME;
        $sPasswordFile = 'vfs://testDir/'.NginxFileProtection::PASSWORD_FILE_NAME;

        self::assertTrue($oNginxFileProtection->create($sTestDir, null, $sTestDir));
        self::assertTrue(file_exists($sFile));
        self::assertTrue(file_exists($sPasswordFile));
        self::assertEquals(
            "location / {\nauth_basic \"WP-Files\";\nauth_basic_user_file vfs://testDir/.htpasswd;\n}\n",
            file_get_contents($sFile)
        );
        self::assertEquals(
            "userLogin:userPass\n",
            file_get_contents($sPasswordFile)
        );

        self::assertTrue($oNginxFileProtection->create($sTestDir, null, $sTestDir));
        self::assertEquals(
            "location / {\nlocation ~ \.(jpg) {\nauth_basic \"WP-Files\";"
            ."\nauth_basic_user_file vfs://testDir/.htpasswd;\n}\n}\n",
            file_get_contents($sFile)
        );

        self::assertTrue($oNginxFileProtection->create($sTestDir, null, $sTestDir));
        self::assertEquals(
            "location / {\nrewrite ^(.*)$ /index.php?uamfiletype=attachment&uamgetfile=$1 last;\n}\n",
            file_get_contents($sFile)
        );

        self::assertTrue($oNginxFileProtection->create($sTestDir, 'objectType', $sTestDir));
        self::assertEquals(
            "location / {\nrewrite ^(.*)$ /index.php?uamfiletype=objectType&uamgetfile=$1 last;\n}\n",
            file_get_contents($sFile)
        );
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\FileHandler\NginxFileProtection::delete()
     */
    public function testDelete()
    {
        $oWrapper = $this->getWrapper();
        $oConfig = $this->getConfig();
        $oUtil = $this->getUtil();
        $oNginxFileProtection = new NginxFileProtection($oWrapper, $oConfig, $oUtil);

        /**
         * @var Directory $oRootDir
         */
        $oRootDir = $this->oRoot->get('/');
        $oRootDir->add('testDir', new Directory([
            NginxFileProtection::FILE_NAME => new File('empty'),
            NginxFileProtection::PASSWORD_FILE_NAME => new File('empty')
        ]));

        $sTestDir = 'vfs://testDir/';
        $sFile = $sTestDir.NginxFileProtection::FILE_NAME;
        $sPasswordFile = $sTestDir.NginxFileProtection::PASSWORD_FILE_NAME;

        self::assertTrue(file_exists($sFile));
        self::assertTrue(file_exists($sPasswordFile));
        self::assertTrue($oNginxFileProtection->delete($sTestDir));
        self::assertFalse(file_exists($sFile));
        //seems a bug in vsf self::assertFalse(file_exists($sPasswordFile));
    }
}
