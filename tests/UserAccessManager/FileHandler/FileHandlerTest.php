<?php
/**
 * FileHandlerTest.php
 *
 * The FileHandlerTest unit test class file.
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
 * Class FileHandlerTest
 *
 * @package UserAccessManager\FileHandler
 */
class FileHandlerTest extends UserAccessManagerTestCase
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
     * @return \PHPUnit_Framework_MockObject_MockObject|FileProtectionFactory
     */
    private function getFileProtectionFactory()
    {
        return $this->createMock('\UserAccessManager\FileHandler\FileProtectionFactory');
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\FileHandler\FileHandler::__construct()
     */
    public function testCanCreateInstance()
    {
        $FileHandler = new FileHandler(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getFileProtectionFactory()
        );

        self::assertInstanceOf('\UserAccessManager\FileHandler\FileHandler', $FileHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\FileHandler\FileHandler::getFile()
     * @runInSeparateProcess
     */
    public function testGetFile()
    {
        $Php = $this->getPhp();
        $Php->expects($this->exactly(9))
            ->method('functionExists')
            ->withConsecutive(
                ['finfo_open'],
                ['finfo_open'],
                ['finfo_open'],
                ['finfo_open'],
                ['mime_content_type'],
                ['finfo_open'],
                ['mime_content_type'],
                ['finfo_open'],
                ['mime_content_type']
            )
            ->will($this->onConsecutiveCalls(
                true,
                true,
                true,
                false,
                true,
                false,
                false,
                false,
                false
            ));

        $Php->expects($this->exactly(4))
            ->method('iniGet')
            ->with('safe_mode')
            ->will($this->onConsecutiveCalls('On', '', 'On', 'On'));

        $Php->expects($this->exactly(3))
            ->method('setTimeLimit')
            ->with(30);

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('wpDie')
            ->with(TXT_UAM_FILE_NOT_FOUND_ERROR)
            ->will($this->returnValue(null));

        $Config = $this->getConfig();
        $Config->expects($this->exactly(6))
            ->method('getMimeTypes')
            ->will($this->onConsecutiveCalls(
                ['txt' => 'textFile'],
                ['txt' => 'textFile'],
                ['txt' => 'textFile'],
                ['txt' => 'textFile'],
                ['txt' => 'textFile'],
                ['jpg' => 'pictureFile']
            ));

        $Config->expects($this->exactly(6))
            ->method('getDownloadType')
            ->will($this->onConsecutiveCalls(null, 'fopen', 'fopen', 'fopen', 'fopen', 'fopen'));

        $FileHandler = new FileHandler(
            $Php,
            $Wordpress,
            $Config,
            $this->getFileProtectionFactory()
        );

        /**
         * @var Directory $RootDir
         */
        $RootDir = $this->oRoot->get('/');
        $RootDir->add('testDir', new Directory([
            'testFile.txt' => new File('Test text')
        ]));

        $sTestDir = 'vfs://testDir/';
        $sNotExistingFile = $sTestDir.'notExistingFile.txt';

        $FileHandler->getFile($sNotExistingFile, false);

        $sTestFile = $sTestDir.'testFile.txt';
        $FileHandler->getFile($sTestFile, false);
        self::expectOutputString('Test text');
        self::assertEquals(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9'
            ],
            xdebug_get_headers()
        );

        $FileHandler->getFile($sTestFile, true);
        self::expectOutputString('Test text');
        self::assertEquals(
            [
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9'
            ],
            xdebug_get_headers()
        );

        $FileHandler->getFile($sTestFile, false);
        self::expectOutputString('Test text');
        self::assertEquals(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9'
            ],
            xdebug_get_headers()
        );

        $FileHandler->getFile($sTestFile, false);
        self::expectOutputString('Test text');
        self::assertEquals(
            [
                'Content-Description: File Transfer',
                'Content-type: text/plain;charset=UTF-8',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9'
            ],
            xdebug_get_headers()
        );

        $FileHandler->getFile($sTestFile, false);
        self::expectOutputString('Test text');
        self::assertEquals(
            [
                'Content-Description: File Transfer',
                'Content-Type: textFile',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9'
            ],
            xdebug_get_headers()
        );

        $FileHandler->getFile($sTestFile, false);
        self::expectOutputString('Test text');
        self::assertEquals(
            [
                'Content-Description: File Transfer',
                'Content-Type: application/octet-stream',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9'
            ],
            xdebug_get_headers()
        );
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\FileHandler\FileHandler::createFileProtection()
     */
    public function testCreateFileProtection()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(7))
            ->method('isNginx')
            ->will($this->onConsecutiveCalls(false, false, false, true, true, true, true));

        $Config = $this->getConfig();
        $Config->expects($this->exactly(6))
            ->method('getUploadDirectory')
            ->will($this->onConsecutiveCalls(
                null,
                'uploadDirectory',
                'uploadDirectory',
                'uploadDirectory',
                'uploadDirectory',
                'uploadDirectory',
                'uploadDirectory'
            ));

        $ApacheFileProtection = $this->createMock('\UserAccessManager\FileHandler\ApacheFileProtection');
        $ApacheFileProtection->expects($this->exactly(3))
            ->method('create')
            ->withConsecutive(['uploadDirectory', null], ['uploadDirectory', null], ['otherDirectory', 'objectType'])
            ->will($this->onConsecutiveCalls(false, true, true));

        $NginxFileProtection = $this->createMock('\UserAccessManager\FileHandler\NginxFileProtection');
        $NginxFileProtection->expects($this->exactly(4))
            ->method('create')
            ->withConsecutive(
                ['uploadDirectory', null],
                ['uploadDirectory', null],
                ['uploadDirectory', null],
                ['otherDirectory', 'objectType']
            )
            ->will($this->onConsecutiveCalls(false, true, true, true));

        $FileProtectionFactory = $this->getFileProtectionFactory();
        $FileProtectionFactory->expects($this->exactly(3))
            ->method('createApacheFileProtection')
            ->will($this->returnValue($ApacheFileProtection));
        $FileProtectionFactory->expects($this->exactly(4))
            ->method('createNginxFileProtection')
            ->will($this->returnValue($NginxFileProtection));

        $FileHandler = new FileHandler(
            $this->getPhp(),
            $Wordpress,
            $Config,
            $FileProtectionFactory
        );

        self::assertFalse($FileHandler->createFileProtection());

        self::assertFalse($FileHandler->createFileProtection());
        self::assertTrue($FileHandler->createFileProtection());
        self::assertTrue($FileHandler->createFileProtection('otherDirectory', 'objectType'));

        self::assertFalse($FileHandler->createFileProtection());
        self::assertTrue($FileHandler->createFileProtection());
        self::assertTrue($FileHandler->createFileProtection());
        self::assertTrue($FileHandler->createFileProtection('otherDirectory', 'objectType'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\FileHandler\FileHandler::deleteFileProtection()
     */
    public function testDeleteFileProtection()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(7))
            ->method('isNginx')
            ->will($this->onConsecutiveCalls(false, false, false, true, true, true, true));

        $Config = $this->getConfig();
        $Config->expects($this->exactly(6))
            ->method('getUploadDirectory')
            ->will($this->onConsecutiveCalls(
                null,
                'uploadDirectory',
                'uploadDirectory',
                'uploadDirectory',
                'uploadDirectory',
                'uploadDirectory'
            ));

        $ApacheFileProtection = $this->createMock('\UserAccessManager\FileHandler\ApacheFileProtection');
        $ApacheFileProtection->expects($this->exactly(3))
            ->method('delete')
            ->withConsecutive(['uploadDirectory'], ['uploadDirectory'], ['otherDirectory'])
            ->will($this->onConsecutiveCalls(false, true, true));

        $NginxFileProtection = $this->createMock('\UserAccessManager\FileHandler\NginxFileProtection');
        $NginxFileProtection->expects($this->exactly(4))
            ->method('delete')
            ->withConsecutive(['uploadDirectory'], ['uploadDirectory'], ['uploadDirectory'], ['otherDirectory'])
            ->will($this->onConsecutiveCalls(false, true, true, true));

        $FileProtectionFactory = $this->getFileProtectionFactory();
        $FileProtectionFactory->expects($this->exactly(3))
            ->method('createApacheFileProtection')
            ->will($this->returnValue($ApacheFileProtection));
        $FileProtectionFactory->expects($this->exactly(4))
            ->method('createNginxFileProtection')
            ->will($this->returnValue($NginxFileProtection));

        $FileHandler = new FileHandler(
            $this->getPhp(),
            $Wordpress,
            $Config,
            $FileProtectionFactory
        );

        self::assertFalse($FileHandler->deleteFileProtection());

        self::assertFalse($FileHandler->deleteFileProtection());
        self::assertTrue($FileHandler->deleteFileProtection());
        self::assertTrue($FileHandler->deleteFileProtection('otherDirectory'));

        self::assertFalse($FileHandler->deleteFileProtection());
        self::assertTrue($FileHandler->deleteFileProtection());
        self::assertTrue($FileHandler->deleteFileProtection());
        self::assertTrue($FileHandler->deleteFileProtection('otherDirectory'));
    }
}
