<?php
/**
 * SetupHandler.php
 *
 * The SetupHandler class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

declare(strict_types=1);

namespace UserAccessManager\Setup;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\File\FileHandler;
use UserAccessManager\Setup\Database\DatabaseHandler;
use UserAccessManager\Setup\Database\MissingColumnsException;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class SetupHandler
 *
 * @package UserAccessManager\SetupHandler
 */
class SetupHandler
{
    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var DatabaseHandler
     */
    private $databaseHandler;

    /**
     * @var MainConfig
     */
    private $mainConfig;

    /**
     * @var FileHandler
     */
    private $fileHandler;

    /**
     * SetupHandler constructor.
     * @param Wordpress $wordpress
     * @param Database $database
     * @param DatabaseHandler $databaseHandler
     * @param MainConfig $mainConfig
     * @param FileHandler $fileHandler
     */
    public function __construct(
        Wordpress $wordpress,
        Database $database,
        DatabaseHandler $databaseHandler,
        MainConfig $mainConfig,
        FileHandler $fileHandler
    ) {
        $this->wordpress = $wordpress;
        $this->database = $database;
        $this->databaseHandler = $databaseHandler;
        $this->mainConfig = $mainConfig;
        $this->fileHandler = $fileHandler;
    }

    /**
     * Returns the database handler object.
     * @return DatabaseHandler
     */
    public function getDatabaseHandler(): DatabaseHandler
    {
        return $this->databaseHandler;
    }

    /**
     * Returns all blog of the network.
     * @return integer[]
     */
    public function getBlogIds(): array
    {
        $currentBlogId = $this->database->getCurrentBlogId();
        $blogIds = [$currentBlogId => $currentBlogId];
        $sites = $this->wordpress->getSites();

        foreach ($sites as $site) {
            $blogIds[$site->blog_id] = $site->blog_id;
        }

        return $blogIds;
    }

    /**
     * Creates the needed tables at the database and adds the options
     * @throws MissingColumnsException
     */
    private function runInstall()
    {
        $this->databaseHandler->install();
    }

    /**
     * Installs the user access manager.
     * @param bool $networkWide
     * @throws MissingColumnsException
     */
    public function install($networkWide = false)
    {
        if ($networkWide === true) {
            $blogIds = $this->getBlogIds();

            foreach ($blogIds as $blogId) {
                $this->wordpress->switchToBlog($blogId);
                $this->runInstall();
                $this->wordpress->restoreCurrentBlog();
            }
        } else {
            $this->runInstall();
        }
    }

    /**
     * Updates the user access manager if an old version was installed.
     * @return bool
     */
    public function update(): bool
    {
        $uamVersion = $this->wordpress->getOption('uam_version', '0');

        if (version_compare($uamVersion, '1.0', '<') === true) {
            $this->wordpress->deleteOption('allow_comments_locked');
        }

        if (version_compare($uamVersion, '2.1.7', '<') === true) {
            $this->mainConfig->setConfigParameters(['locked_directory_type' => 'all']);
        }

        return $this->databaseHandler->updateDatabase();
    }

    /**
     * Clean up wordpress if the plugin will be uninstalled.
     * @throws MissingColumnsException
     */
    public function uninstall()
    {
        $blogIds = $this->getBlogIds();

        foreach ($blogIds as $blogId) {
            $this->wordpress->switchToBlog($blogId);
            $this->databaseHandler->removeTables();

            $this->wordpress->deleteOption(MainConfig::MAIN_CONFIG_KEY);
            $this->wordpress->deleteOption('uam_version');
            $this->wordpress->deleteOption('uam_db_version');
            $this->wordpress->restoreCurrentBlog();
        }

        $this->fileHandler->deleteFileProtection();
    }

    /**
     * Remove the htaccess file if the plugin is deactivated.
     * @return bool
     */
    public function deactivate(): bool
    {
        return $this->fileHandler->deleteFileProtection();
    }
}
