<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend\Administration;

use UserAccessManager\Controller\Controller;

class AboutController extends Controller
{
    public const SUPPORTER_FILE = 'supporters.json';
    public const SUPPORTER_FILE_URL = 'https://gm-alex.github.io/user-access-manager/supporters.json';

    private const SUPPORTER_FILE_LIFETIME_IN_SECONDS = 24 * 60 * 60;

    protected ?string $template = 'AdminAbout.php';
    private ?array $supporters = null;

    private function getAllSupporters(): ?array
    {
        if ($this->supporters !== null) {
            return $this->supporters;
        }

        $realPath = rtrim($this->wordpressConfig->getRealPath(), DIRECTORY_SEPARATOR);
        $fileWithPath = $realPath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . self::SUPPORTER_FILE;

        $needsUpdate = $this->php->isFile($fileWithPath) === false
            || $this->php->fileMTime($fileWithPath)
                < $this->wordpress->currentTime('timestamp') - self::SUPPORTER_FILE_LIFETIME_IN_SECONDS;

        $fileContent = ($needsUpdate === true) ? $this->php->fileGetContents(self::SUPPORTER_FILE_URL) : false;

        if ($fileContent !== false) {
            $this->php->filePutContents($fileWithPath, $fileContent);
        } elseif ($this->php->isFile($fileWithPath) === true) {
            $fileContent = $this->php->fileGetContents($fileWithPath);
        }

        return $this->supporters = (is_string($fileContent) === true) ? json_decode($fileContent, true) : [];
    }

    private function getSupporterGroup(string $key): array
    {
        return $this->getAllSupporters()[$key] ?? [];
    }

    public function getSpecialThanks(): array
    {
        return $this->getSupporterGroup('special-thanks');
    }

    public function getTopSupporters(): array
    {
        return $this->getSupporterGroup('top-supporters');
    }

    public function getSupporters(): array
    {
        return $this->getSupporterGroup('supporters');
    }
}
