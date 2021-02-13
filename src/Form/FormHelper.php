<?php
/**
 * FormHelper.php
 *
 * The FormHelper class file.
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

namespace UserAccessManager\Form;

use Exception;
use UserAccessManager\Config\BooleanConfigParameter;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigParameter;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\SelectionConfigParameter;
use UserAccessManager\Config\StringConfigParameter;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class FormHelper
 *
 * @package UserAccessManager\Form
 */
class FormHelper
{
    /**
     * @var Php
     */
    private $php;

    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var MainConfig
     */
    private $config;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * FormHelper constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param MainConfig $config
     * @param FormFactory $formFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        MainConfig $config,
        FormFactory $formFactory
    ) {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->config = $config;
        $this->formFactory = $formFactory;
    }

    /**
     * Returns the right translation string.
     * @param string $ident
     * @param bool $description
     * @param null $objectKey
     * @return mixed|string
     */
    private function getObjectText(string $ident, $description = false, $objectKey = null): string
    {
        $ident .= ($description === true) ? '_DESC' : '';

        if ($objectKey !== null) {
            $objects = $this->wordpress->getPostTypes(['public' => true], 'objects')
                + $this->wordpress->getTaxonomies(['public' => true], 'objects');

            if (isset($objects[$objectKey]) === true) {
                $ident = str_replace(strtoupper($objectKey), 'OBJECT', $ident);
                $text = (defined($ident) === true) ? constant($ident) : $ident;
                $count = substr_count($text, '%s');

                if ($count > 0) {
                    $arguments = $this->php->arrayFill(0, $count, $objects[$objectKey]->labels->name);
                    $text = vsprintf($text, $arguments);
                }

                return $text;
            }
        }

        return (defined($ident) === true) ? constant($ident) : $ident;
    }

    /**
     * @param string $key
     * @param bool $description
     * @return string
     */
    public function getText(string $key, $description = false): string
    {
        return $this->getObjectText(
            'TXT_UAM_' . strtoupper($key) . '_SETTING',
            $description,
            $key
        );
    }

    /**
     * Returns the label for the parameter.
     * @param ConfigParameter $configParameter
     * @param bool $description
     * @param string $objectKey
     * @return string
     */
    public function getParameterText(ConfigParameter $configParameter, $description = false, $objectKey = null): string
    {
        $ident = 'TXT_UAM_' . strtoupper($configParameter->getId());

        return $this->getObjectText(
            $ident,
            $description,
            $objectKey
        );
    }

    /**
     * Creates a multiple form element.
     * @param string $value
     * @param string $label
     * @param ConfigParameter|null $parameter
     * @return MultipleFormElementValue
     * @throws Exception
     */
    public function createMultipleFromElement(
        string $value,
        string $label,
        ?ConfigParameter $parameter = null
    ): MultipleFormElementValue {
        $value = $this->formFactory->createMultipleFormElementValue($value, $label);

        if ($parameter !== null) {
            $convertedParameter = $this->convertConfigParameter($parameter);

            if ($convertedParameter !== null) {
                $value->setSubElement($convertedParameter);
            }
        }

        return $value;
    }

    /**
     * @param SelectionConfigParameter $configParameter
     * @param null|string $objectKey
     * @param array $overwrittenValues
     * @return mixed
     * @throws Exception
     */
    private function convertSelectionParameter(
        SelectionConfigParameter $configParameter,
        $objectKey = null,
        array $overwrittenValues = []
    ) {
        $values = [];

        foreach ($configParameter->getSelections() as $selection) {
            $optionNameKey = 'TXT_UAM_' . strtoupper($configParameter->getId() . '_' . $selection);
            $label = (defined($optionNameKey) === true) ? constant($optionNameKey) : $optionNameKey;

            if ($overwrittenValues === []) {
                $values[] = $this->formFactory->createValueSetFromElementValue($selection, $label);
            } else {
                $parameter = (isset($overwrittenValues[$selection]) === true) ? $overwrittenValues[$selection] : null;
                $values[] = $this->createMultipleFromElement($selection, $label, $parameter);
            }
        }

        $objectMethod = $overwrittenValues === [] ? 'createSelect' : 'createRadio';

        return $this->formFactory->{$objectMethod}(
            $configParameter->getId(),
            $values,
            $configParameter->getValue(),
            $this->getParameterText($configParameter, false, $objectKey),
            $this->getParameterText($configParameter, true, $objectKey)
        );
    }

    /**
     * @param ConfigParameter $configParameter
     * @param null|string $objectKey
     * @param array $overwrittenValues
     * @return null|Input|Radio|Select
     * @throws Exception
     */
    public function convertConfigParameter(
        ConfigParameter $configParameter,
        $objectKey = null,
        array $overwrittenValues = []
    ) {
        if (($configParameter instanceof StringConfigParameter) === true) {
            return $this->formFactory->createInput(
                $configParameter->getId(),
                $configParameter->getValue(),
                $this->getParameterText($configParameter, false, $objectKey),
                $this->getParameterText($configParameter, true, $objectKey)
            );
        } elseif (($configParameter instanceof BooleanConfigParameter) === true) {
            $yes = $this->formFactory->createMultipleFormElementValue(true, TXT_UAM_YES);
            $no = $this->formFactory->createMultipleFormElementValue(false, TXT_UAM_NO);

            return $this->formFactory->createRadio(
                $configParameter->getId(),
                [$yes, $no],
                $configParameter->getValue(),
                $this->getParameterText($configParameter, false, $objectKey),
                $this->getParameterText($configParameter, true, $objectKey)
            );
        } elseif (($configParameter instanceof SelectionConfigParameter) === true) {
            /** @var SelectionConfigParameter $configParameter */
            return $this->convertSelectionParameter($configParameter, $objectKey, $overwrittenValues);
        }

        return null;
    }

    /**
     * Returns the settings form for the given config parameters.
     * @param array $parameters
     * @param string|null $objectKey
     * @return Form
     * @throws Exception
     */
    public function getSettingsForm(array $parameters, $objectKey = null): Form
    {
        $configParameters = $this->config->getConfigParameters();
        $form = $this->formFactory->createFrom();

        foreach ($parameters as $key => $parameter) {
            $overwrittenValues = [];

            if (is_array($parameter) === true) {
                $overwrittenValues = array_map(
                    function ($parameterKey) use ($configParameters) {
                        return isset($configParameters[$parameterKey]) ? $configParameters[$parameterKey] : null;
                    },
                    $parameter
                );
                $parameter = $key;
            }

            if (is_string($parameter) === true && isset($configParameters[$parameter]) === true) {
                $formElement = $this->convertConfigParameter(
                    $configParameters[$parameter],
                    $objectKey,
                    $overwrittenValues
                );

                if ($formElement !== null) {
                    $form->addElement($formElement);
                }
            } elseif (($parameter instanceof FormElement) === true) {
                $form->addElement($parameter);
            }
        }

        return $form;
    }

    /**
     * Converts config parameters to a form.
     * @param Config $config
     * @return Form
     * @throws Exception
     */
    public function getSettingsFormByConfig(Config $config): Form
    {
        $form = $this->formFactory->createFrom();
        $configParameters = $config->getConfigParameters();

        foreach ($configParameters as $configParameter) {
            $formElement = $this->convertConfigParameter($configParameter);

            if ($formElement !== null) {
                $form->addElement($formElement);
            }
        }

        return $form;
    }
}
