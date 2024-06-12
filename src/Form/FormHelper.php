<?php

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

class FormHelper
{
    public function __construct(
        private Php $php,
        private Wordpress $wordpress,
        private MainConfig $config,
        private FormFactory $formFactory
    ) {
    }

    private function getObjectText(string $ident, bool $description = false, $objectKey = null): string
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

    public function getText(string $key, bool $description = false): string
    {
        return $this->getObjectText(
            'TXT_UAM_' . strtoupper($key) . '_SETTING',
            $description,
            $key
        );
    }

    public function getParameterText(
        ConfigParameter $configParameter,
        bool $description = false,
        string $objectKey = null
    ): string {
        $ident = 'TXT_UAM_' . strtoupper($configParameter->getId());

        return $this->getObjectText(
            $ident,
            $description,
            $objectKey
        );
    }

    /**
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
     * @throws Exception
     */
    private function convertSelectionParameter(
        SelectionConfigParameter $configParameter,
        string $objectKey = null,
        array $overwrittenValues = []
    ): mixed {
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
     * @throws Exception
     */
    public function convertConfigParameter(
        ConfigParameter $configParameter,
        string $objectKey = null,
        array $overwrittenValues = []
    ): Input|Radio|Select|null {
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
            return $this->convertSelectionParameter($configParameter, $objectKey, $overwrittenValues);
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function getSettingsForm(array $parameters, string $objectKey = null): Form
    {
        $configParameters = $this->config->getConfigParameters();
        $form = $this->formFactory->createFrom();

        foreach ($parameters as $key => $parameter) {
            $overwrittenValues = [];

            if (is_array($parameter) === true) {
                $overwrittenValues = array_map(
                    function ($parameterKey) use ($configParameters) {
                        return $configParameters[$parameterKey] ?? null;
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
