<?php

declare(strict_types=1);

namespace UserAccessManager\Controller;

use Exception;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Wrapper\Php;

trait BaseControllerTrait
{
    protected ?string $template = null;

    abstract protected function getPhp(): Php;

    abstract protected function getWordpressConfig(): WordpressConfig;

    public function getRequestUrl(): string
    {
        return htmlentities($_SERVER['REQUEST_URI'], ENT_NOQUOTES);
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value) === true) {
            $sanitized = [];

            foreach ($value as $key => $arrayValue) {
                $sanitized[$this->sanitizeValue($key)] = $this->sanitizeValue($arrayValue);
            }

            return $sanitized;
        }

        if (is_string($value) === true) {
            return htmlspecialchars(stripslashes(preg_replace('/\\+(["|\'])/', '$1', $value)), ENT_NOQUOTES);
        }

        return $value;
    }

    public function getRequestParameter(string $name, mixed $default = null): mixed
    {
        $return = (isset($_POST[$name]) === true) ? $this->sanitizeValue($_POST[$name]) : null;

        if ($return === null) {
            $return = (isset($_GET[$name]) === true) ? $this->sanitizeValue($_GET[$name]) : $default;
        }

        return $return;
    }

    protected function getIncludeContents(string $fileName): string
    {
        $realPath = rtrim($this->getWordpressConfig()->getRealPath(), DIRECTORY_SEPARATOR);
        $fileWithPath = implode(DIRECTORY_SEPARATOR, [$realPath, 'src', 'View', $fileName]);

        if (is_file($fileWithPath) === false) {
            return '';
        }

        try {
            ob_start();
            $this->getPhp()->includeFile($this, $fileWithPath);
            $contents = ob_get_contents();
        } catch (Exception $exception) {
            $contents = "Error on including content '$fileWithPath': {$exception->getMessage()}";
        }

        ob_end_clean();

        return $contents;
    }

    public function render(): void
    {
        if ($this->template !== null) {
            echo $this->getIncludeContents($this->template);
        }
    }
}
