<?php

declare(strict_types=1);

namespace UserAccessManager\Cache;

use Exception;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigFactory;
use UserAccessManager\Config\ConfigParameterFactory;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

class FileSystemCacheProvider implements CacheProviderInterface
{
    const ID = 'FileSystemCacheProvider';
    const CONFIG_KEY = 'uam_file_system_cache_provider';
    const CONFIG_PATH = 'fs_cache_path';
    const CONFIG_METHOD = 'fs_cache_method';
    const METHOD_SERIALIZE = 'serialize';
    const METHOD_IGBINARY = 'igbinary';
    const METHOD_JSON = 'json';
    const METHOD_VAR_EXPORT = 'var_export';

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

        if ($method === self::METHOD_SERIALIZE) {
            $this->php->filePutContents($cacheFile, base64_encode(serialize($value)), LOCK_EX);
        } elseif ($method === self::METHOD_IGBINARY) {
            $this->php->filePutContents($cacheFile, $this->php->igbinarySerialize($value), LOCK_EX);
        } elseif ($method === self::METHOD_JSON) {
            $this->php->filePutContents($cacheFile, json_encode($value), LOCK_EX);
        } elseif ($method === self::METHOD_VAR_EXPORT) {
            $this->php->filePutContents(
                $cacheFile,
                "<?php\n\$cachedValue = " . var_export($value, true) . ';',
                LOCK_EX
            );
        }
    }

    /**
     * @throws Exception
     */
    public function get(string $key): mixed
    {
        $method = $this->getCacheMethod();
        $cacheFile = $this->getCacheFile($method, $key);

        if ((file_exists($cacheFile) === true)) {
            if ($method === self::METHOD_SERIALIZE) {
                return unserialize(base64_decode(file_get_contents($cacheFile)));
            } elseif ($method === self::METHOD_IGBINARY) {
                return $this->php->igbinaryUnserialize(file_get_contents($cacheFile));
            } elseif ($method === self::METHOD_JSON) {
                return json_decode(file_get_contents($cacheFile), true);
            } elseif ($method === self::METHOD_VAR_EXPORT) {
                $cachedValue = null;
                include($cacheFile);
                return $cachedValue;
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function invalidate(string $key): void
    {
        $method = $this->getCacheMethod();
        $cacheFile = $this->getCacheFile($method, $key);

        if ((file_exists($cacheFile) === true)) {
            unlink($cacheFile);
        }
    }
}
