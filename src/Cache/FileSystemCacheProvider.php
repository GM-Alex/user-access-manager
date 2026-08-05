<?php

declare(strict_types=1);

namespace UserAccessManager\Cache;

use Exception;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigFactory;
use UserAccessManager\Config\Parameter\ConfigParameterFactory;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

class FileSystemCacheProvider implements CacheProviderInterface
{
    public const ID = 'FileSystemCacheProvider';
    public const CONFIG_KEY = 'uam_file_system_cache_provider';
    public const CONFIG_PATH = 'fs_cache_path';
    public const CONFIG_METHOD = 'fs_cache_method';
    public const METHOD_SERIALIZE = 'serialize';
    public const METHOD_IGBINARY = 'igbinary';
    public const METHOD_JSON = 'json';
    public const METHOD_VAR_EXPORT = 'var_export';

    private ?Config $config = null;
    private ?string $path = null;

    public function __construct(
        private Php $php,
        private Wordpress $wordpress,
        private Util $util,
        private ConfigFactory $configFactory,
        private ConfigParameterFactory $configParameterFactory
    ) {
    }

    public function getId(): string
    {
        return self::ID;
    }

    /**
     * @throws Exception
     */
    private function getPath(): string
    {
        if ($this->path === null) {
            $this->path = $this->getConfig()->getParameterValue(self::CONFIG_PATH);

            if ($this->util->endsWith($this->path, DIRECTORY_SEPARATOR) === false) {
                $this->path .= DIRECTORY_SEPARATOR;
            }
        }

        return $this->path;
    }

    /**
     * @throws Exception
     */
    public function init(): void
    {
        $path = $this->getPath();

        if (is_dir($path) === false) {
            $this->php->mkdir($path, 0775, true);
        }

        $htaccessFile = $path . '.htaccess';

        if (file_exists($htaccessFile) === false) {
            file_put_contents($htaccessFile, 'Deny from all');
        }
    }

    /**
     * @throws Exception
     */
    public function getConfig(): Config
    {
        if ($this->config === null) {
            $this->config = $this->configFactory->createConfig(self::CONFIG_KEY);

            $selections = [
                self::METHOD_SERIALIZE,
                self::METHOD_JSON,
                self::METHOD_VAR_EXPORT
            ];

            if ($this->php->functionExists('igbinary_serialize') === true) {
                $selections[] = self::METHOD_IGBINARY;
            }

            $configParameters = [
                self::CONFIG_PATH => $this->configParameterFactory->createStringConfigParameter(
                    self::CONFIG_PATH,
                    $this->wordpress->getHomePath() . 'cache/uam'
                ),
                self::CONFIG_METHOD => $this->configParameterFactory->createSelectionConfigParameter(
                    self::CONFIG_METHOD,
                    self::METHOD_VAR_EXPORT,
                    $selections
                )
            ];
            $this->config->setDefaultConfigParameters($configParameters);
        }

        return $this->config;
    }

    /**
     * @throws Exception
     */
    private function getCacheMethod(): ?string
    {
        $method = (string) $this->getConfig()->getParameterValue(self::CONFIG_METHOD);

        if ($method === self::METHOD_IGBINARY
            && ($this->php->functionExists('igbinary_serialize') === false
                || $this->php->functionExists('igbinary_unserialize') === false)
        ) {
            $method = null;
        }

        return $method;
    }

    /**
     * @throws Exception
     */
    private function getCacheFile(?string $method, string $key): string
    {
        $cacheFile = $this->getPath() . $key;
        $cacheFile .= ($method === self::METHOD_VAR_EXPORT) ? '.php' : '.cache';

        return $cacheFile;
    }

    /**
     * @throws Exception
     */
    public function add(string $key, mixed $value): void
    {
        $method = $this->getCacheMethod();
        $cacheFile = $this->getCacheFile($method, $key);

        $content = match ($method) {
            self::METHOD_SERIALIZE => base64_encode(serialize($value)),
            self::METHOD_IGBINARY => $this->php->igbinarySerialize($value),
            self::METHOD_JSON => json_encode($value),
            self::METHOD_VAR_EXPORT => "<?php\n\$cachedValue = " . var_export($value, true) . ';',
            default => null
        };

        if ($content !== null) {
            $this->php->filePutContents($cacheFile, $content, LOCK_EX);
        }
    }

    private function includeCachedValue(string $cacheFile): mixed
    {
        $cachedValue = null;
        include($cacheFile);

        return $cachedValue;
    }

    /**
     * @throws Exception
     */
    public function get(string $key): mixed
    {
        $method = $this->getCacheMethod();
        $cacheFile = $this->getCacheFile($method, $key);

        if (file_exists($cacheFile) === false) {
            return null;
        }

        return match ($method) {
            self::METHOD_SERIALIZE => unserialize(base64_decode(file_get_contents($cacheFile))),
            self::METHOD_IGBINARY => $this->php->igbinaryUnserialize(file_get_contents($cacheFile)),
            self::METHOD_JSON => json_decode(file_get_contents($cacheFile), true),
            self::METHOD_VAR_EXPORT => $this->includeCachedValue($cacheFile),
            default => null
        };
    }

    /**
     * @throws Exception
     */
    public function invalidate(string $key): void
    {
        $method = $this->getCacheMethod();
        $cacheFile = $this->getCacheFile($method, $key);

        if (file_exists($cacheFile) === true) {
            unlink($cacheFile);
        }
    }
}
