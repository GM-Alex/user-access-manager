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

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\File\Protection\ApacheFileProtection;
use UserAccessManager\File\FileHandler;
use UserAccessManager\File\Protection\FileProtectionFactory;
use UserAccessManager\File\Protection\NginxFileProtection;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;
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

    /**
     * Records header() calls made through the Php wrapper, mimicking
     * xdebug_get_headers(): case-insensitive in-place replacement per header name,
     * while status lines feed the response code instead of the header list. This
     * keeps the assertions independent of the SAPI and the xdebug mode.
     */
    private function captureHeaders($php, array &$capturedHeaders, &$responseCode): void
    {
        $php->method('header')->will($this->returnCallback(
            function ($header) use (&$capturedHeaders, &$responseCode) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches) === 1) {
                    $responseCode = (int) $matches[1];
                    return;
                }

                $capturedHeaders[strtolower(explode(':', $header)[0])] = $header;
            }
        ));
    }

    private function createFileHandler(
        ?Php $php = null,
        ?Wordpress $wordpress = null,
        ?WordpressConfig $wordpressConfig = null,
        ?MainConfig $mainConfig = null,
        ?FileProtectionFactory $fileProtectionFactory = null
    ): FileHandler {
        return new FileHandler(
            $php ?? $this->getPhp(),
            $wordpress ?? $this->getWordpress(),
            $wordpressConfig ?? $this->getWordpressConfig(),
            $mainConfig ?? $this->getMainConfig(),
            $fileProtectionFactory ?? $this->getFileProtectionFactory()
        );
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
     * @covers ::isRangeRequest()
     * @covers ::isInlineFile()
     * @covers ::getFileMimeType()
     * @covers ::clearBuffer()
     * @covers ::deliverFile()
     * @covers ::addXSendFileHeader()
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

        $capturedHeaders = [];
        $responseCode = false;
        $this->captureHeaders($php, $capturedHeaders, $responseCode);

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('wpDie')
            ->with(TXT_UAM_FILE_NOT_FOUND_ERROR_MESSAGE, TXT_UAM_FILE_NOT_FOUND_ERROR_TITLE, ['response' => 404])
            ->will($this->returnValue(null));
        $wordpress->expects($this->once())
            ->method('isApacheModuleLoaded')
            ->with('mod_xsendfile')
            ->will($this->returnValue(true));

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

        $fileHandler = $this->createFileHandler($php, $wordpress, $wordpressConfig, $mainConfig);

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
        $notExistingFile = $testDir . 'notExistingFile.txt';

        $fileHandler->getFile($notExistingFile, false);

        $testFileOne = $testDir . 'testFile.txt';
        $testFileTwo = $testDir . 'testFile2.txt';
        $testFileThree = $testDir . 'testFile3.pdf';

        echo 'output'; //Test output must be cleared by getFile method
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals('Test text', self::getActualOutput());
        self::assertEqualsCanonicalizing(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(200, $responseCode);

        $fileHandler->getFile($testFileTwo, true);
        self::assertEquals('Test text2', self::getActualOutput());
        self::assertEqualsCanonicalizing(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: inline; filename="testFile2.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 10'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(200, $responseCode);

        $fileHandler->getFile($testFileThree, false);
        self::assertEquals('Test text3', self::getActualOutput());
        self::assertEqualsCanonicalizing(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: inline; filename="testFile3.pdf"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 10'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(200, $responseCode);

        $fileHandler->getFile($testFileOne, false);
        self::assertEquals('Test text', self::getActualOutput());
        self::assertEqualsCanonicalizing(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(200, $responseCode);

        $fileHandler->getFile($testFileTwo, false);
        self::assertEquals('Test text2', self::getActualOutput());
        self::assertEqualsCanonicalizing(
            [
                'Content-Description: File Transfer',
                'Content-Type: textFile',
                'Content-Disposition: attachment; filename="testFile2.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 10'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(200, $responseCode);

        $_SERVER['REQUEST_METHOD'] = 'something=0-4';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals('Test text2Test text', self::getActualOutput());
        self::assertEqualsCanonicalizing(
            [
                'Content-Description: File Transfer',
                'Content-Type: application/octet-stream',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9'
            ],
            array_values($capturedHeaders)
        );
        self::expectOutputString('Test text2Test text');
        self::assertSame(200, $responseCode);
        $capturedHeaders = [];
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals('Test text2Test text', self::getActualOutput());
        self::assertEqualsCanonicalizing(
            [
                'X-Sendfile: vfs://testDir/testFile.txt',
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(200, $responseCode);
    }

    /**
     * @group  unit
     * @covers ::getFile()
     * @covers ::getFileMimeType()
     * @covers ::addDefaultHeader()
     * @covers ::deliverFile()
     */
    public function testGetFileNormalizesExtensionAndName()
    {
        $php = $this->getPhp();
        $php->method('functionExists')->will($this->returnValueMap([
            ['finfo_open', false],
            ['mime_content_type', false]
        ]));
        $php->method('iniGet')->will($this->returnValue(0));
        $php->method('setTimeLimit');
        $php->method('fread')->will($this->returnCallback(function ($handle, $length) {
            return fread($handle, $length);
        }));
        $php->expects($this->once())->method('callExit');

        $capturedHeaders = [];
        $responseCode = false;
        $this->captureHeaders($php, $capturedHeaders, $responseCode);

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->method('getMimeTypes')->will($this->returnValue(['txt' => 'myMime']));

        $mainConfig = $this->getMainConfig();
        $mainConfig->method('getDownloadType')->will($this->returnValue('fopen'));
        $mainConfig->method('getInlineFiles')->will($this->returnValue(''));

        $fileHandler = $this->createFileHandler(
            $php,
            wordpressConfig: $wordpressConfig,
            mainConfig: $mainConfig
        );

        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('files', new Directory(['A B.TXT' => new File('data')]));

        // Uppercase extension must be lowercased to match the mime map and the
        // space in the file name must be replaced for the Content-Disposition header.
        $fileHandler->getFile('vfs://files/A B.TXT', false);
        self::assertEqualsCanonicalizing(
            [
                'Content-Description: File Transfer',
                'Content-Type: myMime',
                'Content-Disposition: attachment; filename="A_B.TXT"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 4'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(200, $responseCode);
    }

    /**
     * @group  unit
     * @covers ::getFile()
     * @covers ::deliverFile()
     */
    public function testGetFileViaNginxXSendFile()
    {
        $php = $this->getPhp();
        $php->method('functionExists')->will($this->returnValueMap([
            ['finfo_open', false],
            ['mime_content_type', false]
        ]));
        $php->method('iniGet')->will($this->returnValue(0));
        $php->expects($this->once())->method('callExit');

        $capturedHeaders = [];
        $responseCode = false;
        $this->captureHeaders($php, $capturedHeaders, $responseCode);

        $wordpress = $this->getWordpress();
        $wordpress->method('isNginx')->will($this->returnValue(true));

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->method('getMimeTypes')->will($this->returnValue(['txt' => 'myMime']));

        $mainConfig = $this->getMainConfig();
        $mainConfig->method('getDownloadType')->will($this->returnValue('xsendfile'));
        $mainConfig->method('getInlineFiles')->will($this->returnValue(''));

        $fileHandler = $this->createFileHandler($php, $wordpress, $wordpressConfig, $mainConfig);

        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('ABSPATH', new Directory([
            'uploads' => new Directory(['test.txt' => new File('data')])
        ]));

        // On nginx the file is served through an internal X-Accel-Redirect: the
        // ABSPATH prefix is stripped and the /uam-files prefix is prepended.
        $fileHandler->getFile('vfs://ABSPATH/uploads/test.txt', false);
        self::assertEqualsCanonicalizing(
            [
                'X-Accel-Redirect: /uam-filesvfs:///uploads/test.txt',
                'Content-Description: File Transfer',
                'Content-Type: myMime',
                'Content-Disposition: attachment; filename="test.txt"'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(200, $responseCode);
    }

    /**
     * @group  unit
     * @covers ::getFile()
     * @covers ::getFileMimeType()
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

        $capturedHeaders = [];
        $responseCode = false;
        $this->captureHeaders($php, $capturedHeaders, $responseCode);

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->exactly(8))
            ->method('getMimeTypes')
            ->will($this->returnValue(['txt' => 'textFile']));

        $mainConfig = $this->getMainConfig();
        $mainConfig->expects($this->exactly(2))
            ->method('getDownloadType')
            ->will($this->returnValue(null));

        $fileHandler = $this->createFileHandler(
            $php,
            wordpressConfig: $wordpressConfig,
            mainConfig: $mainConfig
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
        $notExistingFile = $testDir . 'notExistingFile.txt';

        $fileHandler->getFile($notExistingFile, false);

        $testFileOne = $testDir . 'testFile.txt';
        $testFileTwo = $testDir . 'testFile2.txt';

        $_SERVER['HTTP_RANGE'] = 'something=0-4';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals('Test text', self::getActualOutput());
        self::assertEqualsCanonicalizing(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(200, $responseCode);

        unset($_SERVER['HTTP_RANGE']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEquals('Test text', self::getActualOutput());
        self::assertEqualsCanonicalizing(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(200, $responseCode);

        $_SERVER['HTTP_RANGE'] = 'something=0-4';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEqualsCanonicalizing(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9',
                'Content-Range: */9'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(416, $responseCode);

        $_SERVER['HTTP_RANGE'] = 'bytes';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEqualsCanonicalizing(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9',
                'Content-Range: */9'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(416, $responseCode);

        $_SERVER['HTTP_RANGE'] = 'bytes=4-4';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEqualsCanonicalizing(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9',
                'Content-Range: */9'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(416, $responseCode);

        $_SERVER['HTTP_RANGE'] = 'bytes=5-4';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEqualsCanonicalizing(
            [
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Transfer-Encoding: binary',
                'Content-Length: 9',
                'Content-Range: */9'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(416, $responseCode);

        $_SERVER['HTTP_RANGE'] = 'bytes=1-4';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEqualsCanonicalizing(
            [
                'Content-Transfer-Encoding: binary',
                'Accept-Ranges: bytes',
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Range: bytes 1-4/9',
                'Content-Length: 4'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(206, $responseCode);

        $_SERVER['HTTP_RANGE'] = 'bytes=2';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEqualsCanonicalizing(
            [
                'Content-Transfer-Encoding: binary',
                'Accept-Ranges: bytes',
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Range: bytes 2-8/9',
                'Content-Length: 7'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(206, $responseCode);

        $_SERVER['HTTP_RANGE'] = 'bytes=a-10';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEqualsCanonicalizing(
            [
                'Content-Transfer-Encoding: binary',
                'Accept-Ranges: bytes',
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Range: bytes 0-8/9',
                'Content-Length: 9'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(206, $responseCode);

        $_SERVER['HTTP_RANGE'] = 'bytes=10-a';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEqualsCanonicalizing(
            [
                'Content-Transfer-Encoding: binary',
                'Accept-Ranges: bytes',
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Length: 9',
                'Content-Range: */9'
            ],
            array_values($capturedHeaders)
        );

        $_SERVER['HTTP_RANGE'] = 'bytes=1-2,3-4';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEqualsCanonicalizing(
            [
                'Content-Description: File Transfer',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Range: */9',
                'Content-Transfer-Encoding: binary',
                'Accept-Ranges: bytes',
                'Content-Type: multipart/x-byteranges; boundary=g45d64df96bmdf4sdgh45hf5',
                'Content-Length: 248'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(206, $responseCode);

        $capturedHeaders = [];
        $_SERVER['HTTP_RANGE'] = 'bytes=-4';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileOne, false);
        self::assertEqualsCanonicalizing(
            [
                'Content-Transfer-Encoding: binary',
                'Accept-Ranges: bytes',
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile.txt"',
                'Content-Range: bytes 5-8/9',
                'Content-Length: 4'
            ],
            array_values($capturedHeaders)
        );
        self::assertSame(206, $responseCode);

        $_SERVER['HTTP_RANGE'] = 'bytes=0-';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $fileHandler->getFile($testFileTwo, false);
        self::assertEqualsCanonicalizing(
            [
                'Content-Transfer-Encoding: binary',
                'Accept-Ranges: bytes',
                'Content-Description: File Transfer',
                'Content-Type: text/plain; charset=us-ascii',
                'Content-Disposition: attachment; filename="testFile2.txt"',
                'Content-Range: bytes 0-1024/1025',
                'Content-Length: 1025'
            ],
            array_values($capturedHeaders)
        );
    }


    /**
     * @group  unit
     * @covers ::readFilePartly()
     * @throws ReflectionException
     */
    public function testReadFilePartlyStopsWhenConnectionIsAborted()
    {
        $rootDir = $this->root->get('/');
        $rootDir->add('readPartlyDir', new Directory([
            'testFile.txt' => new File(str_repeat('a', 1025))
        ]));

        $php = $this->getPhp();
        $php->method('iniGet')->will($this->returnValue(1));
        $php->expects($this->once())
            ->method('fread')
            ->will($this->returnValue('chunk'));
        $php->expects($this->once())
            ->method('connectionStatus')
            ->will($this->returnValue(1));
        $php->expects($this->once())
            ->method('fClose');
        // clearBuffer must flush the output buffer.
        $php->expects($this->once())
            ->method('flush');

        $fileHandler = $this->createFileHandler($php);

        $fileHandler = self::callMethod(
            $fileHandler,
            'readFilePartly',
            [fopen('vfs://readPartlyDir/testFile.txt', 'r'), 2048]
        );

        self::assertNull($fileHandler);
    }

    /**
     * @group  unit
     * @covers ::getRanges()
     * @covers ::getSeekStartEnd()
     * @throws ReflectionException
     */
    public function testGetRangesStopsOnTheFirstInvalidRange()
    {
        $fileHandler = $this->createFileHandler();

        $_SERVER['HTTP_RANGE'] = 'bytes=5-4,1-2';
        self::assertSame([], self::callMethod($fileHandler, 'getRanges', [9]));

        $_SERVER['HTTP_RANGE'] = 'bytes=1-2,3-4';
        self::assertSame([[1, 2], [3, 4]], self::callMethod($fileHandler, 'getRanges', [9]));

        unset($_SERVER['HTTP_RANGE']);
    }

    /**
     * @group  unit
     * @covers ::deliverFilePartial()
     * @throws ReflectionException
     */
    public function testDeliverFilePartialEchoesEveryRangePart()
    {
        $rootDir = $this->root->get('/');
        $rootDir->add('partialDir', new Directory([
            'testFile.txt' => new File('Test text')
        ]));

        $php = $this->getPhp();
        $php->method('functionExists')->with('finfo_open')->will($this->returnValue(true));
        // A non-zero output buffering keeps clearBuffer from wiping the echoed range parts.
        $php->method('iniGet')->will($this->returnValue(1));
        $php->method('connectionStatus')->will($this->returnValue(0));
        $php->method('fread')->will($this->returnValue('chunk'));
        $seekOffsets = [];
        $php->method('fseek')->will($this->returnCallback(
            function ($handle, int $offset) use (&$seekOffsets): int {
                $seekOffsets[] = $offset;
                return 0;
            }
        ));

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->method('getMimeTypes')->will($this->returnValue(['txt' => 'textFile']));

        $fileHandler = $this->createFileHandler($php, wordpressConfig: $wordpressConfig);

        $_SERVER['HTTP_RANGE'] = 'bytes=1-2,3-4';
        self::callMethod($fileHandler, 'deliverFilePartial', ['vfs://partialDir/testFile.txt', false]);
        unset($_SERVER['HTTP_RANGE']);

        $output = self::getActualOutput();
        self::assertStringContainsString('Content-Range: bytes 1-2/9', $output);
        self::assertStringContainsString('Content-Range: bytes 3-4/9', $output);
        // Every part must stream its own range, not the first one twice.
        self::assertSame([1, 3], $seekOffsets);
    }

    /**
     * @group  unit
     * @covers ::getExtraContents()
     * @throws ReflectionException
     */
    public function testGetExtraContentsReturnsAllRangeParts()
    {
        $php = $this->getPhp();
        $php->expects($this->once())
            ->method('functionExists')
            ->with('finfo_open')
            ->will($this->returnValue(true));
        // The opened finfo resource must be closed again.
        $php->expects($this->once())
            ->method('fInfoClose');

        $fileHandler = $this->createFileHandler($php);

        $rootDir = $this->root->get('/');
        $rootDir->add('extraContentsDir', new Directory([
            'testFile.txt' => new File('Test text')
        ]));

        $contentLength = null;
        $boundary = null;
        $extraContents = self::callMethod(
            $fileHandler,
            'getExtraContents',
            ['vfs://extraContentsDir/testFile.txt', [[1, 2], [3, 4]], &$contentLength, &$boundary]
        );

        self::assertCount(3, $extraContents);
        self::assertStringContainsString('Content-Range: bytes 1-2/9', $extraContents[0]);
        self::assertStringContainsString('Content-Range: bytes 3-4/9', $extraContents[1]);
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

        $fileHandler = $this->createFileHandler(
            wordpress: $wordpress,
            wordpressConfig: $wordpressConfig,
            fileProtectionFactory: $fileProtectionFactory
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

        $fileHandler = $this->createFileHandler(
            wordpress: $wordpress,
            wordpressConfig: $wordpressConfig,
            fileProtectionFactory: $fileProtectionFactory
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

        $fileHandler = $this->createFileHandler(
            wordpress: $wordpress,
            wordpressConfig: $wordpressConfig,
            fileProtectionFactory: $fileProtectionFactory
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
     * @covers ::getXSendFileTestFilePath()
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

        $php = $this->getPhp();
        $php->expects($this->once())->method('callExit');
        $capturedHeaders = [];
        $responseCode = false;
        $this->captureHeaders($php, $capturedHeaders, $responseCode);

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->once())
            ->method('getUploadDirectory')
            ->will($this->returnValue($uploadDir));

        $fileHandler = $this->createFileHandler($php, wordpressConfig: $wordpressConfig);

        self::assertFalse(file_exists($uploadDir . FileHandler::X_SEND_FILE_TEST_FILE));
        $fileHandler->deliverXSendFileTestFile();
        self::assertEqualsCanonicalizing(
            [
                'X-Sendfile: vfs://uploadDir//xSendFileTestFile',
                'Content-Type: application/octet-stream',
                'Content-Disposition: attachment; filename="xSendFileTestFile"'
            ],
            array_values($capturedHeaders)
        );
        self::assertFalse($responseCode);
        self::assertTrue(file_exists($uploadDir . FileHandler::X_SEND_FILE_TEST_FILE));
    }

    /**
     * @group  unit
     * @covers ::removeXSendFileTestFile()
     */
    public function testRemoveXSendFileTestFile()
    {
        $uploadDir = 'vfs://uploadDir/';
        $expectedFile = $uploadDir . DIRECTORY_SEPARATOR . FileHandler::X_SEND_FILE_TEST_FILE;

        // The test file exists and gets removed.
        $presentConfig = $this->getWordpressConfig();
        $presentConfig->expects($this->once())
            ->method('getUploadDirectory')
            ->will($this->returnValue($uploadDir));

        $presentPhp = $this->getPhp();
        $presentPhp->expects($this->once())
            ->method('isFile')
            ->with($expectedFile)
            ->will($this->returnValue(true));
        $presentPhp->expects($this->once())
            ->method('unlink')
            ->with($expectedFile);

        $this->createFileHandler($presentPhp, wordpressConfig: $presentConfig)->removeXSendFileTestFile();

        // The test file is missing and nothing gets removed.
        $missingConfig = $this->getWordpressConfig();
        $missingConfig->expects($this->once())
            ->method('getUploadDirectory')
            ->will($this->returnValue($uploadDir));

        $missingPhp = $this->getPhp();
        $missingPhp->expects($this->once())
            ->method('isFile')
            ->with($expectedFile)
            ->will($this->returnValue(false));
        $missingPhp->expects($this->never())
            ->method('unlink');

        $this->createFileHandler($missingPhp, wordpressConfig: $missingConfig)->removeXSendFileTestFile();
    }
}
