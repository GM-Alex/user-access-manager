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
 * @version   SVN: $id$
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
        $fileHandler = new FileHandler(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getFileProtectionFactory()
        );

        self::assertInstanceOf('\UserAccessManager\FileHandler\FileHandler', $fileHandler);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\FileHandler\FileHandler::getFile()
     * @runInSeparateProcess
     */
    public function testGetFile()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(9))
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

        $php->expects($this->exactly(4))
            ->method('iniGet')
            ->with('safe_mode')
            ->will($this->onConsecutiveCalls('On', '', 'On', 'On'));

        $php->expects($this->exactly(3))
            ->method('setTimeLimit')
            ->with(30);

        $php->expects($this->exactly(4))
            ->method('fread')
            ->with($this->anything(), 1024)
            ->will($this->returnCallback(function ($handle, $length) {
                return fread($handle, $length);
            }));

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('wpDie')
            ->with(TXT_UAM_FILE_NOT_FOUND_ERROR)
            ->will($this->returnValue(null));

        $config = $this->getConfig();
        $config->expects($this->exactly(6))
            ->method('getMimeTypes')
            ->will($this->onConsecutiveCalls(
                ['txt' => 'textFile'],
                ['txt' => 'textFile'],
                ['txt' => 'textFile'],
                ['txt' => 'textFile'],
                ['txt' => 'textFile'],
                ['jpg' => 'pictureFile']
            ));

        $config->expects($this->exactly(6))
            ->method('getDownloadType')
            ->will($this->onConsecutiveCalls(null, 'fopen', 'fopen', 'fopen', 'fopen', 'fopen'));

        $fileHandler = new FileHandler(
            $php,
            $wordpress,
            $config,
            $this->getFileProtectionFactory()
        );

        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('testDir', new Directory([
            'testFile.txt' => new File('Test text')
        ]));

        $testDir = 'vfs://testDir/';
        $notExistingFile = $testDir.'notExistingFile.txt';

        $fileHandler->getFile($notExistingFile, false);

        $testFile = $testDir.'testFile.txt';
        $fileHandler->getFile($testFile, false);
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

        $fileHandler->getFile($testFile, true);
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

        $fileHandler->getFile($testFile, false);
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

        $fileHandler->getFile($testFile, false);
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

        $fileHandler->getFile($testFile, false);
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

        $fileHandler->getFile($testFile, false);
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
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(7))
            ->method('isNginx')
            ->will($this->onConsecutiveCalls(false, false, false, true, true, true, true));

        $config = $this->getConfig();
        $config->expects($this->exactly(6))
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

        $apacheFileProtection = $this->createMock('\UserAccessManager\FileHandler\ApacheFileProtection');
        $apacheFileProtection->expects($this->exactly(3))
            ->method('create')
            ->withConsecutive(['uploadDirectory', null], ['uploadDirectory', null], ['otherDirectory', 'objectType'])
            ->will($this->onConsecutiveCalls(false, true, true));

        $nginxFileProtection = $this->createMock('\UserAccessManager\FileHandler\NginxFileProtection');
        $nginxFileProtection->expects($this->exactly(4))
            ->method('create')
            ->withConsecutive(
                ['uploadDirectory', null],
                ['uploadDirectory', null],
                ['uploadDirectory', null],
                ['otherDirectory', 'objectType']
            )
            ->will($this->onConsecutiveCalls(false, true, true, true));

        $fileProtectionFactory = $this->getFileProtectionFactory();
        $fileProtectionFactory->expects($this->exactly(3))
            ->method('createApacheFileProtection')
            ->will($this->returnValue($apacheFileProtection));
        $fileProtectionFactory->expects($this->exactly(4))
            ->method('createNginxFileProtection')
            ->will($this->returnValue($nginxFileProtection));

        $fileHandler = new FileHandler(
            $this->getPhp(),
            $wordpress,
            $config,
            $fileProtectionFactory
        );

        self::assertFalse($fileHandler->createFileProtection());

        self::assertFalse($fileHandler->createFileProtection());
        self::assertTrue($fileHandler->createFileProtection());
        self::assertTrue($fileHandler->createFileProtection('otherDirectory', 'objectType'));

        self::assertFalse($fileHandler->createFileProtection());
        self::assertTrue($fileHandler->createFileProtection());
        self::assertTrue($fileHandler->createFileProtection());
        self::assertTrue($fileHandler->createFileProtection('otherDirectory', 'objectType'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\FileHandler\FileHandler::deleteFileProtection()
     */
    public function testDeleteFileProtection()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(7))
            ->method('isNginx')
            ->will($this->onConsecutiveCalls(false, false, false, true, true, true, true));

        $config = $this->getConfig();
        $config->expects($this->exactly(6))
            ->method('getUploadDirectory')
            ->will($this->onConsecutiveCalls(
                null,
                'uploadDirectory',
                'uploadDirectory',
                'uploadDirectory',
                'uploadDirectory',
                'uploadDirectory'
            ));

        $apacheFileProtection = $this->createMock('\UserAccessManager\FileHandler\ApacheFileProtection');
        $apacheFileProtection->expects($this->exactly(3))
            ->method('delete')
            ->withConsecutive(['uploadDirectory'], ['uploadDirectory'], ['otherDirectory'])
            ->will($this->onConsecutiveCalls(false, true, true));

        $nginxFileProtection = $this->createMock('\UserAccessManager\FileHandler\NginxFileProtection');
        $nginxFileProtection->expects($this->exactly(4))
            ->method('delete')
            ->withConsecutive(['uploadDirectory'], ['uploadDirectory'], ['uploadDirectory'], ['otherDirectory'])
            ->will($this->onConsecutiveCalls(false, true, true, true));

        $fileProtectionFactory = $this->getFileProtectionFactory();
        $fileProtectionFactory->expects($this->exactly(3))
            ->method('createApacheFileProtection')
            ->will($this->returnValue($apacheFileProtection));
        $fileProtectionFactory->expects($this->exactly(4))
            ->method('createNginxFileProtection')
            ->will($this->returnValue($nginxFileProtection));

        $fileHandler = new FileHandler(
            $this->getPhp(),
            $wordpress,
            $config,
            $fileProtectionFactory
        );

        self::assertFalse($fileHandler->deleteFileProtection());

        self::assertFalse($fileHandler->deleteFileProtection());
        self::assertTrue($fileHandler->deleteFileProtection());
        self::assertTrue($fileHandler->deleteFileProtection('otherDirectory'));

        self::assertFalse($fileHandler->deleteFileProtection());
        self::assertTrue($fileHandler->deleteFileProtection());
        self::assertTrue($fileHandler->deleteFileProtection());
        self::assertTrue($fileHandler->deleteFileProtection('otherDirectory'));
    }
}
