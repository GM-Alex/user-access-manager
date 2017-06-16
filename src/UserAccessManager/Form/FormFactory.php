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
namespace UserAccessManager\Form;

/**
 * Class FormFactory
 *
 * @package UserAccessManager\Form
 */
class FormFactory
{
    /**
     * Creates a form object.
     *
     * @return Form
     */
    public function createFrom()
    {
        return new Form();
    }

    /**
     * Creates a ValueSetFormElementValue object.
     *
     * @param mixed  $value
     * @param string $label
     *
     * @return ValueSetFormElementValue
     */
    public function createValueSetFromElementValue($value, $label)
    {
        return new ValueSetFormElementValue($value, $label);
    }

    /**
     * Creates a MultipleFormElementValue object.
     *
     * @param mixed  $value
     * @param string $label
     *
     * @return MultipleFormElementValue
     */
    public function createMultipleFormElementValue($value, $label)
    {
        return new MultipleFormElementValue($value, $label);
    }

    /**
     * Creates a Input object.
     *
     * @param string      $id
     * @param null|mixed  $value
     * @param null|string $label
     * @param null|string $description
     *
     * @return Input
     */
    public function createInput($id, $value = null, $label = null, $description = null)
    {
        return new Input($id, $value, $label, $description);
    }

    /**
     * Creates a Textarea object.
     *
     * @param string      $id
     * @param null|mixed  $value
     * @param null|string $label
     * @param null|string $description
     *
     * @return Textarea
     */
    public function createTextarea($id, $value = null, $label = null, $description = null)
    {
        return new Textarea($id, $value, $label, $description);
    }

    /**
     * Creates a Select object.
     *
     * @param string                     $id
     * @param ValueSetFormElementValue[] $possibleValues
     * @param null|mixed                 $value
     * @param null|string                $label
     * @param null|string                $description
     *
     * @return Select
     */
    public function createSelect($id, array $possibleValues, $value = null, $label = null, $description = null)
    {
        return new Select($id, $possibleValues, $value, $label, $description);
    }

    /**
     * Creates a Radio object.
     *
     * @param string                     $id
     * @param MultipleFormElementValue[] $possibleValues
     * @param null|mixed                 $value
     * @param null|string                $label
     * @param null|string                $description
     *
     * @return Radio
     */
    public function createRadio($id, array $possibleValues, $value = null, $label = null, $description = null)
    {
        return new Radio($id, $possibleValues, $value, $label, $description);
    }
}
