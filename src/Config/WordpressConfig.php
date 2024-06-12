<?php

declare(strict_types=1);

namespace UserAccessManager\Config;

use UserAccessManager\Wrapper\Wordpress;

class WordpressConfig
{
    private ?bool $isPermalinksActive = null;
    private ?array $mimeTypes = null;

    public function __construct(
        private Wordpress $wordpress,
        private string $baseFile
    ) {
    }

    public function atAdminPanel(): bool
    {
        return $this->wordpress->isAdmin();
    }

    public function isPermalinksActive(): ?bool
    {
        if ($this->isPermalinksActive === null) {
            $permalinkStructure = $this->wordpress->getOption('permalink_structure');
            $this->isPermalinksActive = (empty($permalinkStructure) === false);
        }

        return $this->isPermalinksActive;
    }

    public function getUploadDirectory(): ?string
    {
        $wordpressUploadDir = $this->wordpress->getUploadDir();

        if (empty($wordpressUploadDir['error'])) {
            return $wordpressUploadDir['basedir'] . DIRECTORY_SEPARATOR;
        }

        return null;
    }

    public function getMimeTypes(): ?array
    {
        if ($this->mimeTypes === null) {
            $mimeTypes = $this->wordpress->getAllowedMimeTypes();
            $fullMimeTypes = [];

            foreach ($mimeTypes as $extensions => $mineType) {
                $extensions = explode('|', $extensions);

                foreach ($extensions as $extension) {
                    $fullMimeTypes[$extension] = $mineType;
                }
            }

            $this->mimeTypes = $fullMimeTypes;
        }

        return $this->mimeTypes;
    }

    public function getUrlPath(): string
    {
        return $this->wordpress->pluginsUrl('', $this->baseFile) . '/';
    }

    public function getRealPath(): string
    {
        $dirName = dirname($this->baseFile);

        return $this->wordpress->getPluginDir() . DIRECTORY_SEPARATOR
            . $this->wordpress->pluginBasename($dirName) . DIRECTORY_SEPARATOR;
    }
}
