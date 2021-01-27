<?php
/**
 * FormFactory.php
 *
 * The FormFactory class file.
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

/**
 * Class FormFactory
 *
 * @package UserAccessManager\Form
 */
class FormFactory
{
    /**
     * Creates a form object.
     * @return Form
     */
    public function createFrom(): Form
    {
        return new Form();
    }

    /**
     * Creates a ValueSetFormElementValue object.
     * @param mixed $value
     * @param string $label
     * @return ValueSetFormElementValue
     */
    public function createValueSetFromElementValue($value, string $label): ValueSetFormElementValue
    {
        return new ValueSetFormElementValue($value, $label);
    }

    /**
     * Creates a MultipleFormElementValue object.
     * @param mixed $value
     * @param string $label
     * @return MultipleFormElementValue
     */
    public function createMultipleFormElementValue($value, string $label): MultipleFormElementValue
    {
        return new MultipleFormElementValue($value, $label);
    }

    /**
     * Creates a Input object.
     * @param string $id
     * @param null|mixed $value
     * @param null|string $label
     * @param null|string $description
     * @return Input
     */
    public function createInput(string $id, $value = null, $label = null, $description = null): Input
    {
        return new Input($id, $value, $label, $description);
    }

    /**
     * Creates a Textarea object.
     * @param string $id
     * @param null|mixed $value
     * @param null|string $label
     * @param null|string $description
     * @return Textarea
     */
    public function createTextarea(string $id, $value = null, $label = null, $description = null): Textarea
    {
        return new Textarea($id, $value, $label, $description);
    }

    /**
     * Creates a Select object.
     * @param string $id
     * @param ValueSetFormElementValue[] $possibleValues
     * @param null|mixed $value
     * @param null|string $label
     * @param null|string $description
     * @return Select
     */
    public function createSelect(
        string $id,
        array $possibleValues,
        $value = null,
        $label = null,
        $description = null
    ): Select {
        return new Select($id, $possibleValues, $value, $label, $description);
    }

    /**
     * Creates a Radio object.
     * @param string $id
     * @param MultipleFormElementValue[] $possibleValues
     * @param null|mixed $value
     * @param null|string $label
     * @param null|string $description
     * @return Radio
     * @throws Exception
     */
    public function createRadio(
        string $id,
        array $possibleValues,
        $value = null,
        $label = null,
        $description = null
    ): Radio {
        return new Radio($id, $possibleValues, $value, $label, $description);
    }
}
