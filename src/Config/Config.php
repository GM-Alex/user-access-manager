<?php
/**
 * Config.php
 *
 * The Config class file.
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

namespace UserAccessManager\Config;

use Exception;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class Config
 *
 * @package UserAccessManager\Config
 */
class Config
{
    /**
     * @var Wordpress
     */
    protected $wordpress;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var array
     */
    protected $wpOptions = [];

    /**
     * @var ConfigParameter[]
     */
    protected $defaultConfigParameters = [];

    /**
     * @var null|ConfigParameter[]
     */
    protected $configParameters = null;

    /**
     * Config constructor.
     * @param Wordpress $wordpress
     * @param string $key
     */
    public function __construct(
        Wordpress $wordpress,
        string $key
    ) {
        $this->wordpress = $wordpress;
        $this->key = $key;
    }

    /**
     * Returns the WordPress options.
     * @param string $option
     * @return mixed
     */
    public function getWpOption(string $option)
    {
        if (!isset($this->wpOptions[$option]) === true) {
            $this->wpOptions[$option] = $this->wordpress->getOption($option);
        }

        return $this->wpOptions[$option];
    }

    /**
     * Returns the default parameters for the current config.
     * @return ConfigParameter[]
     */
    protected function getDefaultConfigParameters(): array
    {
        return $this->defaultConfigParameters;
    }

    /**
     * Sets the default config parameters
     * @param ConfigParameter[] $defaultConfigParameters
     */
    public function setDefaultConfigParameters(array $defaultConfigParameters)
    {
        $this->defaultConfigParameters = $defaultConfigParameters;
    }

    /**
     * Returns the current settings
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
     * Sets the new config parameters and saves them to the database.
     * @param array $rawParameters
     */
    public function setConfigParameters(array $rawParameters)
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

    /**
     * Flushes the config parameters.
     */
    public function flushConfigParameters()
    {
        $this->defaultConfigParameters = [];
        $this->configParameters = null;
    }

    /**
     * Returns the requested parameter value
     * @param string $parameterName
     * @return mixed
     * @throws Exception
     */
    public function getParameterValueRaw(string $parameterName)
    {
        $options = $this->getConfigParameters();

        if (isset($options[$parameterName]) === false) {
            throw new Exception("Unknown config parameter '{$parameterName}'.");
        }

        return $options[$parameterName]->getValue();
    }

    /**
     * Returns the requested parameter value but suppresses exceptions.
     * @param string $parameterName
     * @return mixed
     */
    public function getParameterValue(string $parameterName)
    {
        try {
            return $this->getParameterValueRaw($parameterName);
        } catch (Exception $exception) {
            return null;
        }
    }
}
