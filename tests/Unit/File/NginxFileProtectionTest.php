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
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\File;

use UserAccessManager\File\NginxFileProtection;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class NginxFileProtectionTest
 *
 * @package UserAccessManager\Tests\Unit\File
 * @coversDefaultClass \UserAccessManager\File\NginxFileProtection
 */
class NginxFileProtectionTest extends UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private $root;

    /**
     * Setup virtual file system.
     */
    protected function setUp(): void
    {
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();
    }

    /**
     * Tear down virtual file system.
     */
    protected function tearDown(): void
    {
        $this->root->unmount();
    }

    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $nginxFileProtection = new NginxFileProtection(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getUtil()
        );

        self::assertInstanceOf(NginxFileProtection::class, $nginxFileProtection);
    }

    /**
     * @group  unit
     * @covers ::getFileNameWithPath()
     */
    public function testGetFileNameWithPath()
    {
        $nginxFileProtection = new NginxFileProtection(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getUtil()
        );

        self::assertEquals('ABSPATH' . NginxFileProtection::FILE_NAME, $nginxFileProtection->getFileNameWithPath());
    }

    /**
     * @group  unit
     * @covers ::create()
     * @covers ::getFileContent()
     * @covers ::getLocation()
     */
    public function testCreate()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('testDir', new Directory());
        $testDir = 'vfs://testDir';

        $mainConfig = $this->getMainConfig();
        $mainConfig->expects($this->exactly(8))
            ->method('getLockedDirectoryType')
            ->will($this->onConsecutiveCalls('all', 'all', 'wordpress', 'wordpress', 'custom', 'custom', 'all', 'all'));

        $mainConfig->expects($this->once())
            ->method('getCustomLockedDirectories')
            ->will($this->returnValue('custom'));

        $nginxFileProtection = new NginxFileProtection(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $mainConfig,
            $this->getUtil()
        );
        $file = 'vfs://testDir/' . NginxFileProtection::FILE_NAME;

        self::assertTrue($nginxFileProtection->create($testDir, null, $testDir));
        self::assertEquals(
            "location / {\n"
            . "rewrite ^([^?]*)$ /index.php?uamfiletype=attachment&uamgetfile=$1 last;\n"
            . "rewrite ^(.*)\\?(((?!uamfiletype).)*)$ /index.php?uamfiletype=attachment&uamgetfile=$1&$2 last;\n"
            . "rewrite ^(.*)\\?(.*)$ /index.php?uamgetfile=$1&$2 last;\n"
            . "}\n",
            file_get_contents($file)
        );

        self::assertTrue($nginxFileProtection->create($testDir, 'objectType', $testDir));
        self::assertEquals(
            "location ^/[0-9]{4}/[0-9]{2} {\n"
            . "rewrite ^([^?]*)$ /index.php?uamfiletype=objectType&uamgetfile=$1 last;\n"
            . "rewrite ^(.*)\\?(((?!uamfiletype).)*)$ /index.php?uamfiletype=objectType&uamgetfile=$1&$2 last;\n"
            . "rewrite ^(.*)\\?(.*)$ /index.php?uamgetfile=$1&$2 last;\n"
            . "}\n",
            file_get_contents($file)
        );

        self::assertTrue($nginxFileProtection->create($testDir, 'objectType', $testDir));
        self::assertEquals(
            "location custom {\n"
            . "rewrite ^([^?]*)$ /index.php?uamfiletype=objectType&uamgetfile=$1 last;\n"
            . "rewrite ^(.*)\\?(((?!uamfiletype).)*)$ /index.php?uamfiletype=objectType&uamgetfile=$1&$2 last;\n"
            . "rewrite ^(.*)\\?(.*)$ /index.php?uamgetfile=$1&$2 last;\n"
            . "}\n",
            file_get_contents($file)
        );

        self::assertFalse($nginxFileProtection->create('invalid', 'invalid'));
    }

    /**
     * @group   unit
     * @covers  ::delete()
     */
    public function testDelete()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(6))
            ->method('unlink')
            ->withConsecutive(
                ['vfs://testDir/uam.conf'],
                ['vfs://testDir/.htpasswd'],
                ['vfs://testDir/uam.conf'],
                ['vfs://testDir/.htpasswd'],
                ['vfs://testDir/uam.conf'],
                ['vfs://testDir/.htpasswd']
            )
            ->will($this->onConsecutiveCalls(true, true, true, false, false, true));

        $nginxFileProtection = new NginxFileProtection(
            $php,
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getUtil()
        );

        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('testDir', new Directory([
            NginxFileProtection::FILE_NAME => new File('empty'),
            NginxFileProtection::PASSWORD_FILE_NAME => new File('empty')
        ]));

        $testDir = 'vfs://testDir/';
        $file = $testDir . NginxFileProtection::FILE_NAME;
        $passwordFile = $testDir . NginxFileProtection::PASSWORD_FILE_NAME;

        self::assertTrue(file_exists($file));
        self::assertTrue(file_exists($passwordFile));
        self::assertTrue($nginxFileProtection->delete($testDir));
        self::assertFalse($nginxFileProtection->delete($testDir));
        self::assertFalse($nginxFileProtection->delete($testDir));
    }
}
