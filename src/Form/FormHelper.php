<?php

declare(strict_types=1);

namespace UserAccessManager\Form;

use Exception;
use UserAccessManager\Config\Parameter\BooleanConfigParameter;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\Parameter\ConfigParameter;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\Parameter\SelectionConfigParameter;
use UserAccessManager\Config\Parameter\StringConfigParameter;
use UserAccessManager\Form\Element\FormElement;
use UserAccessManager\Form\Element\Input;
use UserAccessManager\Form\Element\MultipleFormElementValue;
use UserAccessManager\Form\Element\Radio;
use UserAccessManager\Form\Element\Select;
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

    private function resolveTextConstant(string $ident): string
    {
        return defined($ident) ? constant($ident) : $ident;
    }

    private function getPublicObject(string $objectKey): ?object
    {
        $objects = $this->wordpress->getPostTypes(['public' => true], 'objects')
            + $this->wordpress->getTaxonomies(['public' => true], 'objects');

        return $objects[$objectKey] ?? null;
    }

    private function getObjectText(string $ident, bool $description, ?string $objectKey = null): string
    {
        $ident .= ($description === true) ? '_DESC' : '';
        $object = ($objectKey !== null) ? $this->getPublicObject($objectKey) : null;

        if ($object === null) {
            return $this->resolveTextConstant($ident);
        }

        $text = $this->resolveTextConstant(str_replace(strtoupper($objectKey), 'OBJECT', $ident));
        $placeholderCount = substr_count($text, '%s');

        if ($placeholderCount === 0) {
            return $text;
        }

        return vsprintf($text, $this->php->arrayFill(0, $placeholderCount, $object->labels->name));
    }

    public function getText(string $key, bool $description = false): string
    {
        return $this->getObjectText('TXT_UAM_' . strtoupper($key) . '_SETTING', $description, $key);
    }

    public function getParameterText(
        ConfigParameter $configParameter,
        bool $description = false,
        ?string $objectKey = null
    ): string {
        return $this->getObjectText('TXT_UAM_' . strtoupper($configParameter->getId()), $description, $objectKey);
    }

    /**
     * @throws Exception
     */
    public function createMultipleFormElementValue(
        string $value,
        string $label,
        ?ConfigParameter $parameter = null
    ): MultipleFormElementValue {
        $elementValue = $this->formFactory->createMultipleFormElementValue($value, $label);
        $subElement = ($parameter !== null) ? $this->convertConfigParameter($parameter) : null;

        if ($subElement !== null) {
            $elementValue->setSubElement($subElement);
        }

        return $elementValue;
    }

    /**
     * @throws Exception
     */
    private function convertSelectionParameter(
        SelectionConfigParameter $configParameter,
        ?string $objectKey = null,
        array $overwrittenValues = []
    ): Select|Radio {
        $values = [];

        foreach ($configParameter->getSelections() as $selection) {
            $optionLabel = $this->resolveTextConstant(
                'TXT_UAM_' . strtoupper($configParameter->getId() . '_' . $selection)
            );

            $overwrittenValue = $overwrittenValues[$selection] ?? null;
            $values[] = ($overwrittenValues === [])
                ? $this->formFactory->createValueSetFormElementValue($selection, $optionLabel)
                : $this->createMultipleFormElementValue($selection, $optionLabel, $overwrittenValue);
        }

        $label = $this->getParameterText($configParameter, false, $objectKey);
        $description = $this->getParameterText($configParameter, true, $objectKey);

        if ($overwrittenValues === []) {
            return $this->formFactory->createSelect(
                $configParameter->getId(),
                $values,
                $configParameter->getValue(),
                $label,
                $description
            );
        }

        return $this->formFactory->createRadio(
            $configParameter->getId(),
            $values,
            $configParameter->getValue(),
            $label,
            $description
        );
    }

    /**
     * @throws Exception
     */
    public function convertConfigParameter(
        ConfigParameter $configParameter,
        ?string $objectKey = null,
        array $overwrittenValues = []
    ): Input|Radio|Select|null {
        if ($configParameter instanceof StringConfigParameter) {
            return $this->formFactory->createInput(
                $configParameter->getId(),
                $configParameter->getValue(),
                $this->getParameterText($configParameter, false, $objectKey),
                $this->getParameterText($configParameter, true, $objectKey)
            );
        }

        if ($configParameter instanceof BooleanConfigParameter) {
            return $this->formFactory->createRadio(
                $configParameter->getId(),
                [
                    $this->formFactory->createMultipleFormElementValue(true, TXT_UAM_YES),
                    $this->formFactory->createMultipleFormElementValue(false, TXT_UAM_NO)
                ],
                $configParameter->getValue(),
                $this->getParameterText($configParameter, false, $objectKey),
                $this->getParameterText($configParameter, true, $objectKey)
            );
        }

        if ($configParameter instanceof SelectionConfigParameter) {
            return $this->convertSelectionParameter($configParameter, $objectKey, $overwrittenValues);
        }

        return null;
    }

    /**
     * @throws Exception
     */
    private function addConvertedParameter(
        Form $form,
        ConfigParameter $configParameter,
        ?string $objectKey = null,
        array $overwrittenValues = []
    ): void {
        $formElement = $this->convertConfigParameter($configParameter, $objectKey, $overwrittenValues);

        if ($formElement !== null) {
            $form->addElement($formElement);
        }
    }

    /**
     * @throws Exception
     */
    public function getSettingsForm(array $parameters, ?string $objectKey = null): Form
    {
        $configParameters = $this->config->getConfigParameters();
        $form = $this->formFactory->createForm();

        foreach ($parameters as $key => $parameter) {
            $overwrittenValues = [];

            if (is_array($parameter) === true) {
                $overwrittenValues = array_map(
                    fn($parameterKey) => $configParameters[$parameterKey] ?? null,
                    $parameter
                );
                $parameter = $key;
            }

            if (is_string($parameter) === true && isset($configParameters[$parameter]) === true) {
                $this->addConvertedParameter($form, $configParameters[$parameter], $objectKey, $overwrittenValues);
            } elseif ($parameter instanceof FormElement) {
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
        $form = $this->formFactory->createForm();

        foreach ($config->getConfigParameters() as $configParameter) {
            $this->addConvertedParameter($form, $configParameter);
        }

        return $form;
    }
}
