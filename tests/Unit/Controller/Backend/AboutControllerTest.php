<?php
/**
 * AdminAboutControllerTest.php
 *
 * The AdminAboutControllerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Controller\Backend;

use ReflectionException;
use UserAccessManager\Controller\Backend\AboutController;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class AdminAboutControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Backend
 * @coversDefaultClass \UserAccessManager\Controller\Backend\AboutController
 */
class AboutControllerTest extends UserAccessManagerTestCase
{
    /**
     * @group unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $aboutController = new AboutController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig()
        );

        self::assertInstanceOf(AboutController::class, $aboutController);
    }

    /**
     * @group  unit
     * @covers ::getAllSupporters()
     * @covers ::getSpecialThanks()
     * @covers ::getTopSupporters()
     * @covers ::getSupporters()
     * @throws ReflectionException
     */
    public function testGetSupporters()
    {
        $fileWithPath = '/var/uam/assets/' . AboutController::SUPPORTER_FILE;

        $isFile = false;
        $fileMTime = 0;
        $currentTime = 0;
        $remoteContent = false;
        $localContent = false;
        $writtenContent = null;

        $php = $this->getPhp();
        $php->method('isFile')->will($this->returnCallback(
            function ($file) use (&$isFile, $fileWithPath) {
                self::assertSame($fileWithPath, $file);
                return $isFile;
            }
        ));
        $php->method('fileMTime')->will($this->returnCallback(
            function ($file) use (&$fileMTime, $fileWithPath) {
                self::assertSame($fileWithPath, $file);
                return $fileMTime;
            }
        ));
        $php->method('fileGetContents')->will($this->returnCallback(
            function ($file) use (&$remoteContent, &$localContent, $fileWithPath) {
                if ($file === AboutController::SUPPORTER_FILE_URL) {
                    return $remoteContent;
                }

                self::assertSame($fileWithPath, $file);
                return $localContent;
            }
        ));
        $php->method('filePutContents')->will($this->returnCallback(
            function ($file, $data) use (&$writtenContent, $fileWithPath) {
                self::assertSame($fileWithPath, $file);
                $writtenContent = $data;
                return strlen((string) $data);
            }
        ));

        $wordpress = $this->getWordpress();
        $wordpress->method('currentTime')->will($this->returnCallback(
            function ($type) use (&$currentTime) {
                self::assertSame('timestamp', $type);
                return $currentTime;
            }
        ));

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->method('getRealPath')->will($this->returnValue('/var/uam/'));

        $aboutController = new AboutController($php, $wordpress, $wordpressConfig);

        $load = function () use ($aboutController) {
            self::setValue($aboutController, 'supporters', null);
            return [
                $aboutController->getSpecialThanks(),
                $aboutController->getTopSupporters(),
                $aboutController->getSupporters()
            ];
        };

        $fullJson = '{"special-thanks":["a","b"],"top-supporters":["c","d"],"supporters":["e","f"]}';
        $localJson = '{"special-thanks":["g"],"top-supporters":["h"],"supporters":["i"]}';

        // Missing file -> fetch from the remote URL, persist it locally and decode it.
        $isFile = false;
        $remoteContent = $fullJson;
        $writtenContent = null;
        self::assertSame([['a', 'b'], ['c', 'd'], ['e', 'f']], $load());
        self::assertSame($fullJson, $writtenContent);

        // Existing, exactly at the update threshold (fresh) -> read the local file.
        $isFile = true;
        $fileMTime = 100000;
        $currentTime = 100000 + 24 * 60 * 60;
        $remoteContent = '{"special-thanks":["remote"]}';
        $localContent = $localJson;
        $writtenContent = null;
        self::assertSame([['g'], ['h'], ['i']], $load());
        self::assertNull($writtenContent);

        // Existing, fresh by less than an hour -> still the local file (kills threshold decrease).
        $currentTime = 1000000;
        $fileMTime = $currentTime - (24 * 60 * 60 - 720);
        self::assertSame([['g'], ['h'], ['i']], $load());
        self::assertNull($writtenContent);

        // Existing, stale by 10 minutes past a day -> refetch remote (kills threshold increase).
        $fileMTime = $currentTime - (24 * 60 * 60 + 600);
        $remoteContent = $fullJson;
        self::assertSame([['a', 'b'], ['c', 'd'], ['e', 'f']], $load());
        self::assertSame($fullJson, $writtenContent);

        // Stale, remote fetch fails, but the local file exists -> read the local file.
        $writtenContent = null;
        $remoteContent = false;
        $localContent = $localJson;
        self::assertSame([['g'], ['h'], ['i']], $load());
        self::assertNull($writtenContent);

        // Missing file and remote fetch fails -> no supporters at all.
        $isFile = false;
        $remoteContent = false;
        self::assertSame([[], [], []], $load());
        self::assertNull($writtenContent);
    }
}
