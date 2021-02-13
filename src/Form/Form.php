<?php
/**
 * Form.php
 *
 * The Form class file.
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

/**
 * Class Form
 *
 * @package UserAccessManager\Form
 */
class Form
{
    /**
     * @var FormElement[]
     */
    private $elements = [];

    /**
     * @param FormElement $element
     */
    public function addElement(FormElement $element)
    {
        $this->elements[$element->getId()] = $element;
    }

    /**
     * @return FormElement[]
     */
    public function getElements(): array
    {
        return $this->elements;
    }
}
