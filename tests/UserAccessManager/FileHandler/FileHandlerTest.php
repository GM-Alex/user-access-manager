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

use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class FileHandlerTest
 *
 * @package UserAccessManager\FileHandler
 */
class FileHandlerTest extends \UserAccessManagerTestCase
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
        $oFileHandler = new FileHandler(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getFileProtectionFactory()
        );

        self::assertInstanceOf('\UserAccessManager\FileHandler\FileHandler', $oFileHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\FileHandler\FileHandler::getFile()
     * @runInSeparateProcess
     */
    public function testGetFile()
    {
        $oPhp = $this->getPhp();
        $oPhp->expects($this->exactly(9))
            ->method('functionExists')
            ->withConsecutive(
                ['finfo_open'], ['finfo_open'], ['finfo_open'],
                ['finfo_open'], ['mime_content_type'],
                ['finfo_open'], ['mime_content_type'], ['finfo_open'], ['mime_content_type']
            )
            ->will($this->onConsecutiveCalls(
                true, true, true,
                false, true,
                false, false, false, false
            ));

        $oPhp->expects($this->exactly(4))
            ->method('iniGet')
            ->with('safe_mode')
            ->will($this->onConsecutiveCalls('On', '', 'On', 'On'));

        $oPhp->expects($this->exactly(3))
            ->method('setTimeLimit')
            ->with(30);

        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->once())
            ->method('wpDie')
            ->with(TXT_UAM_FILE_NOT_FOUND_ERROR)
            ->will($this->returnValue(null));

        $oConfig = $this->getConfig();
        $oConfig->expects($this->exactly(6))
            ->method('getMimeTypes')
            ->will($this->onConsecutiveCalls(
                ['txt' => 'textFile'], ['txt' => 'textFile'], ['txt' => 'textFile'],
                ['txt' => 'textFile'], ['txt' => 'textFile'], ['jpg' => 'pictureFile']
            ));

        $oConfig->expects($this->exactly(6))
            ->method('getDownloadType')
            ->will($this->onConsecutiveCalls(null, 'fopen', 'fopen', 'fopen', 'fopen', 'fopen'));

        $oFileHandler = new FileHandler(
            $oPhp,
            $oWordpress,
            $oConfig,
            $this->getFileProtectionFactory()
        );

        /**
         * @var Directory $oRootDir
         */
        $oRootDir = $this->oRoot->get('/');
        $oRootDir->add('testDir', new Directory([
            'testFile.txt' => new File('Test text')
        ]));

        $sTestDir = 'vfs://testDir/';
        $sNotExistingFile = $sTestDir.'notExistingFile.txt';

        $oFileHandler->getFile($sNotExistingFile, false);

        $sTestFile = $sTestDir.'testFile.txt';
        $oFileHandler->getFile($sTestFile, false);
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

        $oFileHandler->getFile($sTestFile, true);
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

        $oFileHandler->getFile($sTestFile, false);
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

        $oFileHandler->getFile($sTestFile, false);
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

        $oFileHandler->getFile($sTestFile, false);
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

        $oFileHandler->getFile($sTestFile, false);
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
        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->exactly(7))
            ->method('isNginx')
            ->will($this->onConsecutiveCalls(false, false, false, true, true, true, true));

        $oConfig = $this->getConfig();
        $oConfig->expects($this->exactly(6))
            ->method('getUploadDirectory')
            ->will($this->onConsecutiveCalls(
                null, 'uploadDirectory', 'uploadDirectory', 'uploadDirectory',
                'uploadDirectory', 'uploadDirectory', 'uploadDirectory'
            ));

        $oApacheFileProtection = $this->createMock('\UserAccessManager\FileHandler\ApacheFileProtection');
        $oApacheFileProtection->expects($this->exactly(3))
            ->method('create')
            ->withConsecutive(['uploadDirectory', null], ['uploadDirectory', null], ['otherDirectory', 'objectType'])
            ->will($this->onConsecutiveCalls(false, true, true));

        $oNginxFileProtection = $this->createMock('\UserAccessManager\FileHandler\NginxFileProtection');
        $oNginxFileProtection->expects($this->exactly(4))
            ->method('create')
            ->withConsecutive(
                ['uploadDirectory', null], ['uploadDirectory', null],
                ['uploadDirectory', null], ['otherDirectory', 'objectType']
            )
            ->will($this->onConsecutiveCalls(false, true, true, true));

        $oFileProtectionFactory = $this->getFileProtectionFactory();
        $oFileProtectionFactory->expects($this->exactly(3))
            ->method('createApacheFileProtection')
            ->will($this->returnValue($oApacheFileProtection));
        $oFileProtectionFactory->expects($this->exactly(4))
            ->method('createNginxFileProtection')
            ->will($this->returnValue($oNginxFileProtection));

        $oFileHandler = new FileHandler(
            $this->getPhp(),
            $oWordpress,
            $oConfig,
            $oFileProtectionFactory
        );

        self::assertFalse($oFileHandler->createFileProtection());

        self::assertFalse($oFileHandler->createFileProtection());
        self::assertTrue($oFileHandler->createFileProtection());
        self::assertTrue($oFileHandler->createFileProtection('otherDirectory', 'objectType'));

        self::assertFalse($oFileHandler->createFileProtection());
        self::assertTrue($oFileHandler->createFileProtection());
        self::assertTrue($oFileHandler->createFileProtection());
        self::assertTrue($oFileHandler->createFileProtection('otherDirectory', 'objectType'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\FileHandler\FileHandler::deleteFileProtection()
     */
    public function testDeleteFileProtection()
    {
        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->exactly(7))
            ->method('isNginx')
            ->will($this->onConsecutiveCalls(false, false, false, true, true, true, true));

        $oConfig = $this->getConfig();
        $oConfig->expects($this->exactly(6))
            ->method('getUploadDirectory')
            ->will($this->onConsecutiveCalls(
                null, 'uploadDirectory', 'uploadDirectory', 'uploadDirectory', 'uploadDirectory', 'uploadDirectory'
            ));

        $oApacheFileProtection = $this->createMock('\UserAccessManager\FileHandler\ApacheFileProtection');
        $oApacheFileProtection->expects($this->exactly(3))
            ->method('delete')
            ->withConsecutive(['uploadDirectory'], ['uploadDirectory'], ['otherDirectory'])
            ->will($this->onConsecutiveCalls(false, true, true));

        $oNginxFileProtection = $this->createMock('\UserAccessManager\FileHandler\NginxFileProtection');
        $oNginxFileProtection->expects($this->exactly(4))
            ->method('delete')
            ->withConsecutive(['uploadDirectory'], ['uploadDirectory'], ['uploadDirectory'], ['otherDirectory'])
            ->will($this->onConsecutiveCalls(false, true, true, true));

        $oFileProtectionFactory = $this->getFileProtectionFactory();
        $oFileProtectionFactory->expects($this->exactly(3))
            ->method('createApacheFileProtection')
            ->will($this->returnValue($oApacheFileProtection));
        $oFileProtectionFactory->expects($this->exactly(4))
            ->method('createNginxFileProtection')
            ->will($this->returnValue($oNginxFileProtection));

        $oFileHandler = new FileHandler(
            $this->getPhp(),
            $oWordpress,
            $oConfig,
            $oFileProtectionFactory
        );

        self::assertFalse($oFileHandler->deleteFileProtection());

        self::assertFalse($oFileHandler->deleteFileProtection());
        self::assertTrue($oFileHandler->deleteFileProtection());
        self::assertTrue($oFileHandler->deleteFileProtection('otherDirectory'));

        self::assertFalse($oFileHandler->deleteFileProtection());
        self::assertTrue($oFileHandler->deleteFileProtection());
        self::assertTrue($oFileHandler->deleteFileProtection());
        self::assertTrue($oFileHandler->deleteFileProtection('otherDirectory'));
    }
}
