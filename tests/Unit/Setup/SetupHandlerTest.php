<?php
/**
 * SetupHandlerTest.php
 *
 * The SetupHandlerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Setup;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Setup\Database\DatabaseHandler;
use UserAccessManager\Setup\Database\MissingColumnsException;
use UserAccessManager\Setup\SetupHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class SetupHandlerTest
 *
 * @package UserAccessManager\Tests\Unit\Setup
 * @coversDefaultClass \UserAccessManager\Setup\SetupHandler
 */
class SetupHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $setupHandler = new SetupHandler(
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getDatabaseHandler(),
            $this->getMainConfig(),
            $this->getFileHandler()
        );

        self::assertInstanceOf(SetupHandler::class, $setupHandler);
    }

    /**
     * @group  unit
     * @covers ::getDatabaseHandler()
     */
    public function testGetDatabaseHandler()
    {
        $setupHandler = new SetupHandler(
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getDatabaseHandler(),
            $this->getMainConfig(),
            $this->getFileHandler()
        );

        self::assertInstanceOf(DatabaseHandler::class, $setupHandler->getDatabaseHandler());
    }

    /**
     * @group  unit
     * @covers ::getBlogIds()
     */
    public function testGetBlogIds()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getSites')
            ->will($this->returnValue($this->getSites()));

        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getCurrentBlogId')
            ->will($this->returnValue(123));

        $setupHandler = new SetupHandler(
            $wordpress,
            $database,
            $this->getDatabaseHandler(),
            $this->getMainConfig(),
            $this->getFileHandler()
        );

        $blogIds = $setupHandler->getBlogIds();
        self::assertEquals([123 => 123, 1 => 1, 2 => 2, 3 => 3], $blogIds);
    }

    /**
     * @group  unit
     * @covers ::install()
     * @covers ::runInstall()
     * @throws MissingColumnsException
     */
    public function testInstall()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getSites')
            ->will($this->returnValue($this->getSites(1)));

        $wordpress->expects($this->once())
            ->method('restoreCurrentBlog')
            ->withConsecutive();

        $wordpress->expects(($this->once()))
            ->method('switchToBlog')
            ->withConsecutive([1]);

        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getCurrentBlogId')
            ->will($this->returnValue(1));

        $databaseHandler = $this->getDatabaseHandler();

        $databaseHandler->expects($this->exactly(3))
            ->method('install');

        $setupHandler = new SetupHandler(
            $wordpress,
            $database,
            $databaseHandler,
            $this->getMainConfig(),
            $this->getFileHandler()
        );

        $setupHandler->install();
        $setupHandler->install();
        $setupHandler->install(true);
    }

    /**
     * @group  unit
     * @covers ::update()
     */
    public function testUpdate()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(4))
            ->method('getOption')
            ->withConsecutive(
                ['uam_version', '0'],
                ['uam_version', '0'],
                ['uam_version', '0'],
                ['uam_version', '0']
            )
            ->will($this->onConsecutiveCalls('0', '1.0', '1.0', '1.0'));

        $wordpress->expects($this->once())
            ->method('deleteOption')
            ->with('allow_comments_locked');

        $databaseHandler = $this->getDatabaseHandler();
        $databaseHandler->expects($this->exactly(4))
            ->method('updateDatabase')
            ->will($this->onConsecutiveCalls(false, false, false, true));

        $mainConfig = $this->getMainConfig();
        $mainConfig->expects($this->exactly(4))
            ->method('setConfigParameters')
            ->with(['locked_directory_type' => 'all']);

        $setupHandler = new SetupHandler(
            $wordpress,
            $this->getDatabase(),
            $databaseHandler,
            $mainConfig,
            $this->getFileHandler()
        );

        self::assertFalse($setupHandler->update());
        self::assertFalse($setupHandler->update());
        self::assertFalse($setupHandler->update());
        self::assertTrue($setupHandler->update());
    }

    /**
     * @group  unit
     * @covers ::uninstall()
     * @throws MissingColumnsException
     */
    public function testUninstall()
    {
        $wordpress = $this->getWordpress();

        $wordpress->expects($this->once())
            ->method('getSites')
            ->will($this->returnValue($this->getSites(2)));

        $wordpress->expects(($this->exactly(6)))
            ->method('deleteOption')
            ->withConsecutive(
                [MainConfig::MAIN_CONFIG_KEY],
                ['uam_version'],
                ['uam_db_version'],
                [MainConfig::MAIN_CONFIG_KEY],
                ['uam_version'],
                ['uam_db_version']
            );

        $wordpress->expects($this->exactly(2))
            ->method('restoreCurrentBlog')
            ->withConsecutive();

        $wordpress->expects(($this->exactly(2)))
            ->method('switchToBlog')
            ->withConsecutive([1], [2]);

        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getCurrentBlogId')
            ->will($this->returnValue(1));

        $databaseHandler = $this->getDatabaseHandler();
        $databaseHandler->expects($this->exactly(2))
            ->method('removeTables');

        $fileHandler = $this->getFileHandler();
        $fileHandler->expects($this->once())
            ->method('deleteFileProtection');

        $setupHandler = new SetupHandler(
            $wordpress,
            $database,
            $databaseHandler,
            $this->getMainConfig(),
            $fileHandler
        );

        $setupHandler->uninstall();
    }

    /**
     * @group  unit
     * @covers ::deactivate()
     */
    public function testDeactivate()
    {
        $fileHandler = $this->getFileHandler();

        $fileHandler->expects($this->exactly(2))
            ->method('deleteFileProtection')
            ->will($this->onConsecutiveCalls(false, true));

        $setupHandler = new SetupHandler(
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getDatabaseHandler(),
            $this->getMainConfig(),
            $fileHandler
        );

        self::assertFalse($setupHandler->deactivate());
        self::assertTrue($setupHandler->deactivate());
    }
}
