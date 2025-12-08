<?php

declare(strict_types=1);

namespace UserAccessManager\Config;

use Exception;
use UserAccessManager\Wrapper\Wordpress;

class Config
{
    protected string $key;
    protected array $wpOptions = [];
    /**
     * @var ConfigParameter[]
     */
    protected array $defaultConfigParameters = [];
    /**
     * @var null|ConfigParameter[]
     */
    protected ?array $configParameters = null;

    public function __construct(
        private Wordpress $wordpress,
        string $key
    ) {
        $this->key = $key;
    }

    public function getWpOption(string $option): mixed
    {
        if (!isset($this->wpOptions[$option]) === true) {
            $this->wpOptions[$option] = $this->wordpress->getOption($option);
        }

        return $this->wpOptions[$option];
    }

    /**
     * @return ConfigParameter[]
     */
    protected function getDefaultConfigParameters(): array
    {
        return $this->defaultConfigParameters;
    }

    /**
     * @param ConfigParameter[] $defaultConfigParameters
     */
    public function setDefaultConfigParameters(array $defaultConfigParameters): void
    {
        $this->defaultConfigParameters = $defaultConfigParameters;
    }

    /**
     * @return ConfigParameter[]
     */
    public function getConfigParameters(): array
    {
        if ($this->configParameters === null) {
            $configParameters = $this->getDefaultConfigParameters();
            $currentOptions = (array) $this->getWpOption($this->key);

            foreach ($currentOptions as $key => $option) {
                if (isset($configParameters[$key])) {
                    $configParameters[$key]->setValue($option);
                }
            }

            $this->configParameters = $configParameters;
        }

        return $this->configParameters;
    }

    /**
     * @param array $rawParameters
     */
    public function setConfigParameters(array $rawParameters): void
    {
        $configParameters = $this->getConfigParameters();

        foreach ($rawParameters as $key => $value) {
            if (isset($configParameters[$key]) === true) {
                $configParameters[$key]->setValue($value);
            }
        }

        $this->configParameters = $configParameters;

        $simpleConfigParameters = [];

        foreach ($configParameters as $parameter) {
            $simpleConfigParameters[$parameter->getId()] = $parameter->getValue();
        }

        $this->wordpress->updateOption($this->key, $simpleConfigParameters);
    }

    public function flushConfigParameters(): void
    {
        $this->defaultConfigParameters = [];
        $this->configParameters = null;
    }

    /**
     * @throws Exception
     */
    public function getParameterValueRaw(string $parameterName): mixed
    {
        $options = $this->getConfigParameters();

        if (isset($options[$parameterName]) === false) {
            throw new Exception("Unknown config parameter '$parameterName'.");
        }

        return $options[$parameterName]->getValue();
    }

    public function getParameterValue(string $parameterName): mixed
    {
        try {
            return $this->getParameterValueRaw($parameterName);
        } catch (Exception) {}

        return null;
    }
}
