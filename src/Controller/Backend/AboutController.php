<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

use UserAccessManager\Controller\Controller;

class AboutController extends Controller
{
    const SUPPORTER_FILE = 'supporters.json';
    const SUPPORTER_FILE_URL = 'https://gm-alex.github.io/user-access-manager/supporters.json';

    protected ?string $template = 'AdminAbout.php';
    private ?array $supporters = null;

    private function getAllSupporters(): ?array
    {
        if ($this->supporters === null) {
            $realPath = rtrim($this->wordpressConfig->getRealPath(), DIRECTORY_SEPARATOR);
            $path = [$realPath, 'assets'];
            $path = implode(DIRECTORY_SEPARATOR, $path) . DIRECTORY_SEPARATOR;
            $fileWithPath = $path . self::SUPPORTER_FILE;
            $needsUpdate = is_file($fileWithPath) === false
                || filemtime($fileWithPath) < $this->wordpress->currentTime('timestamp') - 24 * 60 * 60;
            $fileContent = ($needsUpdate === true) ? @file_get_contents(self::SUPPORTER_FILE_URL) : false;

            if ($fileContent !== false) {
                file_put_contents($fileWithPath, $fileContent);
            } elseif (is_file($fileWithPath) === true) {
                $fileContent = file_get_contents($fileWithPath);
            }

            $this->supporters = (is_string($fileContent) === true) ? json_decode($fileContent, true) : [];
        }

        return $this->supporters;
    }

    public function getSpecialThanks(): array
    {
        $supporters = $this->getAllSupporters();
        return isset($supporters['special-thanks']) === true ? $supporters['special-thanks'] : [];
    }

    public function getTopSupporters(): array
    {
        $supporters = $this->getAllSupporters();
        return isset($supporters['top-supporters']) === true ? $supporters['top-supporters'] : [];
    }

    public function getSupporters(): array
    {
        $supporters = $this->getAllSupporters();
        return isset($supporters['supporters']) === true ? $supporters['supporters'] : [];
    }
}
