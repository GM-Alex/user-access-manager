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
namespace UserAccessManager\Tests\Unit\File;

use UserAccessManager\File\ApacheFileProtection;
use UserAccessManager\File\FileHandler;
use UserAccessManager\File\NginxFileProtection;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class FileHandlerTest
 *
 * @package UserAccessManager\Tests\Unit\File
 * @coversDefaultClass \UserAccessManager\File\FileHandler
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
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $fileHandler = new FileHandler(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getFileProtectionFactory()
        );

        self::assertInstanceOf(FileHandler::class, $fileHandler);
    }

    /**
     * @group  unit
     * @covers ::getFile()
     * @covers ::isInlineFile()
     * @covers ::getFileMineType()
     * @covers ::clearBuffer()
     * @covers ::deliverFile()
     * @covers ::addDefaultHeader()
     * @covers ::deliverFileViaFopen()
     * @runInSeparateProcess
     */
    public function testGetFile()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(10))
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
                ['mime_content_type'],
                ['finfo_open']
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
                false,
                true
            ));

        $php->expects($this->exactly(11))
            ->method('iniGet')
            ->withConsecutive(
                ['output_buffering'],
                ['output_buffering'],
                ['safe_mode'],
                ['output_buffering'],
                ['safe_mode'],
                ['output_buffering'],
                ['safe_mode'],
                ['output_buffering'],
                ['safe_mode'],
                ['output_buffering'],
                ['safe_mode']
            )
            ->will($this->onConsecutiveCalls(
                0,
                0,
                'On',
                0,
                'On',
                0,
                '',
                0,
                'On',
                1,
                'On'
            ));

        $php->expects($this->exactly(4))
            ->method('setTimeLimit')
            ->with(30);

        $php->expects($this->exactly(5))
            ->method('fread')
            ->with($this->anything(), 1024)
            ->will($this->returnCallback(function ($handle, $length) {
                return fread($handle, $length);
            }));

        $php->expects($this->exactly(7))
            ->method('callExit');

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('wpDie')
            ->with(TXT_UAM_FILE_NOT_FOUND_ERROR_MESSAGE, TXT_UAM_FILE_NOT_FOUND_ERROR_TITLE, ['response' => 404])
            ->will($this->returnValue(null));

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->exactly(7))
            ->method('getMimeTypes')
            ->will($this->onConsecutiveCalls(
                ['txt' => 'textFile'],
                ['txt' => 'textFile'],
                ['txt' => 'textFile'],
                ['txt' => 'textFile'],
                ['txt' => 'textFile'],
                ['jpg' => 'pictureFile'],
                ['txt' => 'textFile']
            ));

        $mainConfig = $this->getMainConfig();
        $mainConfig->expects($this->exactly(7))
            ->method('getDownloadType')
            ->will($this->onConsecutiveCalls(null, 'fopen', 'fopen', 'fopen', 'fopen', 'fopen', 'xsendfile'));


        $mainConfig->expects($this->exactly(6))
            ->method('getInlineFiles')
            ->will($this->returnValue('pdf ,some'));

        $fileHandler = new FileHandler(
            $php,
            $wordpress,
            $wordpressConfig,
            $mainConfig,
            $this->getFileProtectionFactory()
        );

        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('testDir', new Directory([
            'testFile.txt' => new File('Test text'),
            'testFile2.txt' => new File('Test text2'),
            'testFile3.pdf' => new File('Test text3')
        ]));

        $testDir = 'vfs://testDir/';
        $notExistingFile = $testDir.'notExistingFile.txt';

        $fileHandler->getFile($notExistingFile, false);

        $testFileOne = $testDir.'testFile.txt';
        $testFileTwo = $testDir.'testFile2.txt';
        $testFileThree = $testDir.'testFile3.pdf';

        echo 'output'; //Test output must be cleared by getFile method
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals('Test text', self::getActualOutput());
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
        self::assertEquals(false, http_response_code());

        $fileHandler->getFile($testFileTwo, true);
        self::assertEquals('Test text2', self::getActualOutput());
        self::assertEquals(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: inline; filename="testFile2.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 10'
            ],
            xdebug_get_headers()
        );
        self::assertEquals(false, http_response_code());

        $fileHandler->getFile($testFileThree, false);
        self::assertEquals('Test text3', self::getActualOutput());
        self::assertEquals(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: inline; filename="testFile3.pdf"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 10'
            ],
            xdebug_get_headers()
        );
        self::assertEquals(false, http_response_code());

        $fileHandler->getFile($testFileOne, false);
        self::assertEquals('Test text', self::getActualOutput());
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
        self::assertEquals(false, http_response_code());

        $fileHandler->getFile($testFileTwo, false);
        self::assertEquals('Test text2', self::getActualOutput());
        self::assertEquals(
            [
                'Content-Description: File Transfer',
                'Content-Type: textFile',
                'Content-Disposition: attachment; filename="testFile2.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 10'
            ],
            xdebug_get_headers()
        );
        self::assertEquals(false, http_response_code());

        $_SERVER['REQUEST_METHOD'] = 'something=0-4';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals('Test text2Test text', self::getActualOutput());
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
        self::expectOutputString('Test text2Test text');
        self::assertEquals(false, http_response_code());

        header_remove();
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals('Test text2Test text', self::getActualOutput());
        self::assertEquals(
            [
                'X-Sendfile: vfs://testDir/testFile.txt',
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
            ],
            xdebug_get_headers()
        );
        self::assertEquals(false, http_response_code());
    }

    /**
     * @group  unit
     * @covers ::getFile()
     * @covers ::getFileMineType()
     * @covers ::clearBuffer()
     * @covers ::deliverFilePartial()
     * @covers ::getRanges()
     * @covers ::getExtraContents()
     * @covers ::getSeekStartEnd()
     * @covers ::readFilePartly()
     * @runInSeparateProcess
     */
    public function testGetPartialFile()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(8))
            ->method('functionExists')
            ->with('finfo_open')
            ->will($this->returnValue(true));

        $php->expects($this->exactly(8))
            ->method('fread')
            ->withConsecutive(
                [$this->anything(), 4],
                [$this->anything(), 7],
                [$this->anything(), 9],
                [$this->anything(), 2],
                [$this->anything(), 2],
                [$this->anything(), 4],
                [$this->anything(), 1024],
                [$this->anything(), 1]
            )
            ->will($this->returnCallback(function ($handle, $length) {
                return fread($handle, $length);
            }));

        $php->expects($this->exactly(13))
            ->method('callExit');

        $php->expects($this->once())
            ->method('fClose');

        $php->expects($this->exactly(8))
            ->method('connectionStatus')
            ->will($this->onConsecutiveCalls(0, 0, 0, 0, 1, 0, 0, 0));

        $php->expects($this->exactly(11))
            ->method('iniGet')
            ->with('output_buffering')
            ->will($this->returnValue(0));

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->exactly(8))
            ->method('getMimeTypes')
            ->will($this->returnValue(['txt' => 'textFile']));

        $mainConfig = $this->getMainConfig();
        $mainConfig->expects($this->exactly(2))
            ->method('getDownloadType')
            ->will($this->returnValue(null));

        $fileHandler = new FileHandler(
            $php,
            $this->getWordpress(),
            $wordpressConfig,
            $mainConfig,
            $this->getFileProtectionFactory()
        );

        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('testDir', new Directory([
            'testFile.txt' => new File('Test text'),
            'testFile2.txt' => new File(str_repeat('a', 1025))
        ]));

        $testDir = 'vfs://testDir/';
        $notExistingFile = $testDir.'notExistingFile.txt';

        $fileHandler->getFile($notExistingFile, false);

        $testFileOne = $testDir.'testFile.txt';
        $testFileTwo = $testDir.'testFile2.txt';

        $_SERVER['HTTP_RANGE'] = 'something=0-4';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals('Test text', self::getActualOutput());
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
        self::assertEquals(false, http_response_code());

        unset($_SERVER['HTTP_RANGE']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals('Test text', self::getActualOutput());
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
        self::assertEquals(false, http_response_code());

        $_SERVER['HTTP_RANGE'] = 'something=0-4';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9',
                'Content-Range: */9'
            ],
            xdebug_get_headers()
        );
        self::assertEquals(416, http_response_code());

        $_SERVER['HTTP_RANGE'] = 'bytes';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9',
                'Content-Range: */9'
            ],
            xdebug_get_headers()
        );
        self::assertEquals(416, http_response_code());

        $_SERVER['HTTP_RANGE'] = 'bytes=4-4';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9',
                'Content-Range: */9'
            ],
            xdebug_get_headers()
        );
        self::assertEquals(416, http_response_code());

        $_SERVER['HTTP_RANGE'] = 'bytes=5-4';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9',
                'Content-Range: */9'
            ],
            xdebug_get_headers()
        );
        self::assertEquals(416, http_response_code());

        $_SERVER['HTTP_RANGE'] = 'bytes=1-4';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals(
            [
                'Content-Transfer-Encoding: binary',
                'Accept-Ranges: bytes',
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Range: bytes 1-4/9',
                'Content-Length: 4'
            ],
            xdebug_get_headers()
        );
        self::assertEquals(206, http_response_code());

        $_SERVER['HTTP_RANGE'] = 'bytes=2';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals(
            [
                'Content-Transfer-Encoding: binary',
                'Accept-Ranges: bytes',
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Range: bytes 2-8/9',
                'Content-Length: 7'
            ],
            xdebug_get_headers()
        );
        self::assertEquals(206, http_response_code());

        $_SERVER['HTTP_RANGE'] = 'bytes=a-10';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals(
            [
                'Content-Transfer-Encoding: binary',
                'Accept-Ranges: bytes',
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Range: bytes 0-8/9',
                'Content-Length: 9'
            ],
            xdebug_get_headers()
        );
        self::assertEquals(206, http_response_code());

        $_SERVER['HTTP_RANGE'] = 'bytes=10-a';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals(
            [
                'Content-Transfer-Encoding: binary',
                'Accept-Ranges: bytes',
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Length: 9',
                'Content-Range: */9'
            ],
            xdebug_get_headers()
        );

        $_SERVER['HTTP_RANGE'] = 'bytes=1-2,3-4';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals(
            [
                'Content-Description: File Transfer',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Range: */9',
                'Content-Transfer-Encoding: binary',
                'Accept-Ranges: bytes',
                'Content-Type: multipart/x-byteranges; boundary=g45d64df96bmdf4sdgh45hf5',
                'Content-Length: 248'
            ],
            xdebug_get_headers()
        );
        self::assertEquals(206, http_response_code());

        $_SERVER['HTTP_RANGE'] = 'bytes=-4';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals(
            [
                'Content-Transfer-Encoding: binary',
                'Accept-Ranges: bytes',
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Range: bytes 5-8/9',
                'Content-Length: 4'
            ],
            xdebug_get_headers()
        );
        self::assertEquals(206, http_response_code());

        $_SERVER['HTTP_RANGE'] = 'bytes=0-';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileTwo, false);
        self::assertEquals(
            [
                'Content-Transfer-Encoding: binary',
                'Accept-Ranges: bytes',
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile2.txt"',
                'Content-Range: bytes 0-1024/1025',
                'Content-Length: 1025'
            ],
            xdebug_get_headers()
        );
    }


    /**
     * @group  unit
     * @covers ::getFileProtectionFileName()
     * @covers ::getCurrentFileProtectionHandler()
     */
    public function testGetFileProtectionFileName()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('isNginx')
            ->will($this->onConsecutiveCalls(false, true));

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->exactly(2))
            ->method('getUploadDirectory')
            ->will($this->returnValue('uploadDirectory'));


        $apacheFileProtection = $this->createMock(ApacheFileProtection::class);
        $apacheFileProtection->expects($this->once())
            ->method('getFileNameWithPath')
            ->with('uploadDirectory')
            ->will($this->returnValue('apacheUploadDirectory'));

        $nginxFileProtection = $this->createMock(NginxFileProtection::class);
        $nginxFileProtection->expects($this->once())
            ->method('getFileNameWithPath')
            ->with('uploadDirectory')
            ->will($this->returnValue('nginxUploadDirectory'));

        $fileProtectionFactory = $this->getFileProtectionFactory();
        $fileProtectionFactory->expects($this->once())
            ->method('createApacheFileProtection')
            ->will($this->returnValue($apacheFileProtection));
        $fileProtectionFactory->expects($this->once())
            ->method('createNginxFileProtection')
            ->will($this->returnValue($nginxFileProtection));

        $fileHandler = new FileHandler(
            $this->getPhp(),
            $wordpress,
            $wordpressConfig,
            $this->getMainConfig(),
            $fileProtectionFactory
        );

        self::assertEquals('apacheUploadDirectory', $fileHandler->getFileProtectionFileName());
        self::assertEquals('nginxUploadDirectory', $fileHandler->getFileProtectionFileName());
    }

    /**
     * @group  unit
     * @covers ::createFileProtection()
     * @covers ::getCurrentFileProtectionHandler()
     */
    public function testCreateFileProtection()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(7))
            ->method('isNginx')
            ->will($this->onConsecutiveCalls(false, false, false, true, true, true, true));

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->exactly(6))
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

        $apacheFileProtection = $this->createMock(ApacheFileProtection::class);
        $apacheFileProtection->expects($this->exactly(3))
            ->method('create')
            ->withConsecutive(['uploadDirectory', null], ['uploadDirectory', null], ['otherDirectory', 'objectType'])
            ->will($this->onConsecutiveCalls(false, true, true));

        $nginxFileProtection = $this->createMock(NginxFileProtection::class);
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
            $wordpressConfig,
            $this->getMainConfig(),
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
     * @covers ::deleteFileProtection()
     * @covers ::getCurrentFileProtectionHandler()
     */
    public function testDeleteFileProtection()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(7))
            ->method('isNginx')
            ->will($this->onConsecutiveCalls(false, false, false, true, true, true, true));

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->exactly(6))
            ->method('getUploadDirectory')
            ->will($this->onConsecutiveCalls(
                null,
                'uploadDirectory',
                'uploadDirectory',
                'uploadDirectory',
                'uploadDirectory',
                'uploadDirectory'
            ));

        $apacheFileProtection = $this->createMock(ApacheFileProtection::class);
        $apacheFileProtection->expects($this->exactly(3))
            ->method('delete')
            ->withConsecutive(['uploadDirectory'], ['uploadDirectory'], ['otherDirectory'])
            ->will($this->onConsecutiveCalls(false, true, true));

        $nginxFileProtection = $this->createMock(NginxFileProtection::class);
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
            $wordpressConfig,
            $this->getMainConfig(),
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

    /**
     * @group  unit
     * @covers ::deliverXSendFileTestFile()
     * @runInSeparateProcess
     */
    public function testDeliverXSendFileTestFile()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('uploadDir', new Directory([
            'testFile.txt' => new File('Test text'),
            'testFile2.txt' => new File('Test text2'),
            'testFile3.txt' => new File('Test text3')
        ]));

        $uploadDir = 'vfs://uploadDir/';

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->once())
            ->method('getUploadDirectory')
            ->will($this->returnValue($uploadDir));

        $fileHandler = new FileHandler(
            $this->getPhp(),
            $this->getWordpress(),
            $wordpressConfig,
            $this->getMainConfig(),
            $this->getFileProtectionFactory()
        );

        self::assertFalse(file_exists($uploadDir.FileHandler::X_SEND_FILE_TEST_FILE));
        $fileHandler->deliverXSendFileTestFile();
        self::assertEquals(
            [
                'X-Sendfile: vfs://uploadDir//xSendFileTestFile',
                'Content-Type: application/octet-stream',
                'Content-Disposition: attachment; filename="xSendFileTestFile"'
            ],
            xdebug_get_headers()
        );
        self::assertEquals(false, http_response_code());
        self::assertTrue(file_exists($uploadDir.FileHandler::X_SEND_FILE_TEST_FILE));
    }

    /**
     * @group  unit
     * @covers ::removeXSendFileTestFile()
     */
    public function testRemoveXSendFileTestFile()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('uploadDir', new Directory([
            FileHandler::X_SEND_FILE_TEST_FILE => new File('success')
        ]));

        $uploadDir = 'vfs://uploadDir/';

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->once())
            ->method('getUploadDirectory')
            ->will($this->returnValue($uploadDir));

        $fileHandler = new FileHandler(
            $this->getPhp(),
            $this->getWordpress(),
            $wordpressConfig,
            $this->getMainConfig(),
            $this->getFileProtectionFactory()
        );

        self::assertTrue(file_exists($uploadDir.FileHandler::X_SEND_FILE_TEST_FILE));
        $fileHandler->removeXSendFileTestFile();
        self::assertFalse(file_exists($uploadDir.FileHandler::X_SEND_FILE_TEST_FILE));
    }
}
