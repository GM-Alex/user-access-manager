<?php

declare(strict_types=1);

namespace UserAccessManager\Setup;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\File\FileHandler;
use UserAccessManager\Setup\Database\DatabaseHandler;
use UserAccessManager\Setup\Database\MissingColumnsException;
use UserAccessManager\Wrapper\Wordpress;

class SetupHandler
{
    public function __construct(
        private Wordpress $wordpress,
        private Database $database,
        private DatabaseHandler $databaseHandler,
        private MainConfig $mainConfig,
        private FileHandler $fileHandler
    ) {
    }

    public function getDatabaseHandler(): DatabaseHandler
    {
        return $this->databaseHandler;
    }

    /**
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
     * @throws MissingColumnsException
     */
    private function runInstall(): void
    {
        $this->databaseHandler->install();
    }

    /**
     * @throws MissingColumnsException
     */
    public function install(bool $networkWide = false): void
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

    public function update(): bool
    {
        $uamVersion = (string) $this->wordpress->getOption('uam_version', '0');

        if (version_compare($uamVersion, '1.0', '<') === true) {
            $this->wordpress->deleteOption('allow_comments_locked');
        }

        if (version_compare($uamVersion, '2.1.7', '<') === true) {
            $this->mainConfig->setConfigParameters(['locked_directory_type' => 'all']);
        }

        return $this->databaseHandler->updateDatabase();
    }

    /**
     * @throws MissingColumnsException
     */
    public function uninstall(): void
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

    public function deactivate(): bool
    {
        return $this->fileHandler->deleteFileProtection();
    }
}
