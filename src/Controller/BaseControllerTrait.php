<?php

declare(strict_types=1);

namespace UserAccessManager\Controller;

use Exception;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Wrapper\Php;

trait BaseControllerTrait
{
    abstract protected function getPhp(): Php;
    abstract protected function getWordpressConfig(): WordpressConfig;
    protected ?string $template = null;

    public function getRequestUrl(): string
    {
        return htmlentities($_SERVER['REQUEST_URI'], ENT_NOQUOTES);
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if (is_object($value) === true) {
            return $value;
        } elseif (is_array($value) === true) {
            $newValue = [];

            foreach ($value as $key => $arrayValue) {
                $sanitizedKey = $this->sanitizeValue($key);
                $newValue[$sanitizedKey] = $this->sanitizeValue($arrayValue);
            }

            $value = $newValue;
        } elseif (is_string($value) === true) {
            $value = preg_replace('/\\+(["|\'])/', '$1', $value);
            $value = stripslashes($value);
            $value = htmlspecialchars($value, ENT_NOQUOTES);
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
        $contents = '';
        $realPath = rtrim($this->getWordpressConfig()->getRealPath(), DIRECTORY_SEPARATOR);
        $path = [$realPath, 'src', 'View'];
        $path = implode(DIRECTORY_SEPARATOR, $path).DIRECTORY_SEPARATOR;
        $fileWithPath = $path.$fileName;

        if (is_file($fileWithPath) === true) {
            try {
                ob_start();
                $this->getPhp()->includeFile($this, $fileWithPath);
                $contents = ob_get_contents();
                ob_end_clean();
            } catch (Exception $exception) {
                $contents = "Error on including content '$fileWithPath': {$exception->getMessage()}";
                ob_end_clean();
            }
        }

        return $contents;
    }

    public function render(): void
    {
        if ($this->template !== null) {
            echo $this->getIncludeContents($this->template);
        }
    }
}
