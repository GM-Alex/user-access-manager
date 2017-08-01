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
namespace UserAccessManager\Form;

use UserAccessManager\Config\BooleanConfigParameter;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\ConfigParameter;
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
     *
     * @param Php         $php
     * @param Wordpress   $wordpress
     * @param MainConfig  $config
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
     *
     * @param string $ident
     * @param bool   $description
     * @param string $objectKey
     *
     * @return mixed|string
     */
    private function getObjectText($ident, $description = false, $objectKey = null)
    {
        $ident .= ($description === true) ? '_DESC' : '';

        if ($objectKey !== null) {
            $objects = $this->wordpress->getPostTypes(['public' => true], 'objects')
                + $this->wordpress->getTaxonomies(['public' => true], 'objects');

            if (isset($objects[$objectKey]) === true) {
                $ident = str_replace(strtoupper($objectKey), 'OBJECT', $ident);
                $text = (defined($ident) === true) ? constant($ident) : $ident;
                $count = substr_count($text, '%s');
                $arguments = $this->php->arrayFill(0, $count, $objects[$objectKey]->labels->name);
                return vsprintf($text, $arguments);
            }
        }

        return (defined($ident) === true) ? constant($ident) : $ident;
    }

    /**
     * @param string $key
     * @param bool   $description
     *
     * @return string
     */
    public function getText($key, $description = false)
    {
        return $this->getObjectText(
            'TXT_UAM_'.strtoupper($key).'_SETTING',
            $description,
            $key
        );
    }

    /**
     * Returns the label for the parameter.
     *
     * @param ConfigParameter $configParameter
     * @param bool            $description
     * @param string          $objectKey
     *
     * @return string
     */
    public function getParameterText(ConfigParameter $configParameter, $description = false, $objectKey = null)
    {
        $ident = 'TXT_UAM_'.strtoupper($configParameter->getId());

        return $this->getObjectText(
            $ident,
            $description,
            $objectKey
        );
    }

    /**
     * Converts a config parameter to a form element.
     *
     * @param ConfigParameter $configParameter
     * @param string          $objectKey
     *
     * @return null|Input|Radio|Select
     */
    public function convertConfigParameter(ConfigParameter $configParameter, $objectKey = null)
    {
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
            $possibleValues = [];
            /**
             * @var SelectionConfigParameter $configParameter
             */
            $selections = $configParameter->getSelections();

            foreach ($selections as $selection) {
                $optionNameKey = 'TXT_UAM_'.strtoupper($configParameter->getId().'_'.$selection);
                $label = (defined($optionNameKey) === true) ? constant($optionNameKey) : $optionNameKey;
                $possibleValues[] = $this->formFactory->createValueSetFromElementValue($selection, $label);
            }

            return $this->formFactory->createSelect(
                $configParameter->getId(),
                $possibleValues,
                $configParameter->getValue(),
                $this->getParameterText($configParameter, false, $objectKey),
                $this->getParameterText($configParameter, true, $objectKey)
            );
        }

        return null;
    }

    /**
     * Returns the settings form for the given config parameters.
     *
     * @param array       $parameters
     * @param string|null $objectKey
     *
     * @return \UserAccessManager\Form\Form
     */
    public function getSettingsForm(array $parameters, $objectKey = null)
    {
        $configParameters = $this->config->getConfigParameters();
        $form = $this->formFactory->createFrom();

        foreach ($parameters as $parameter) {
            if (is_string($parameter) === true && isset($configParameters[$parameter]) === true) {
                $formElement = $this->convertConfigParameter($configParameters[$parameter], $objectKey);

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
     *
     * @param Config $config
     *
     * @return Form
     */
    public function getSettingsFormByConfig(Config $config)
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
