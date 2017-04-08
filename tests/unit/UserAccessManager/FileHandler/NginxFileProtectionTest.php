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
            $this->getPhp(),
            $this->getWordpress(),
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
        $oWordpress = $this->getWordpress();

        /**
         * @var \stdClass $oUser
         */
        $oUser = $this->getMockBuilder('\WP_User')->getMock();
        $oUser->user_login = 'userLogin';
        $oUser->user_pass = 'userPass';

        $oWordpress->expects($this->exactly(2))
            ->method('getCurrentUser')
            ->will($this->returnValue($oUser));

        $oConfig = $this->getConfig();

        $oConfig->expects($this->exactly(5))
            ->method('isPermalinksActive')
            ->will($this->onConsecutiveCalls(false, false, true, true, true));

        $oConfig->expects($this->exactly(2))
            ->method('getLockFileTypes')
            ->will($this->onConsecutiveCalls(null, 'selected'));

        $oConfig->expects($this->once())
            ->method('getLockedFileTypes')
            ->will($this->returnValue('png,jpg'));

        $oConfig->expects($this->once())
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

        $oNginxFileProtection = new NginxFileProtection(
            $this->getPhp(),
            $oWordpress,
            $oConfig,
            $this->getUtil()
        );
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

        self::assertFalse($oNginxFileProtection->create('invalid', 'invalid'));
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\FileHandler\NginxFileProtection::delete()
     */
    public function testDelete()
    {
        $oPhp = $this->getPhp();
        $oPhp->expects($this->exactly(6))
            ->method('unlink')
            ->will($this->onConsecutiveCalls(true, true, true, false, false, true));

        $oNginxFileProtection = new NginxFileProtection(
            $oPhp,
            $this->getWordpress(),
            $this->getConfig(),
            $this->getUtil()
        );

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
        self::assertFalse($oNginxFileProtection->delete($sTestDir));
        self::assertFalse($oNginxFileProtection->delete($sTestDir));
    }
}
