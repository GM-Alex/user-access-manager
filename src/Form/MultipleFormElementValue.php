<?php
/**
 * MultipleFormElementValue.php
 *
 * The MultipleFormElementValue class file.
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
 * Class MultipleFormElementValue
 *
 * @package UserAccessManager\Form
 */
class MultipleFormElementValue extends ValueSetFormElementValue
{
    /**
     * @var FormElement
     */
    private $subElement;

    /**
     * @param FormElement $subElement
     * @throws Exception
     */
    public function setSubElement(FormElement $subElement)
    {
        if ($subElement instanceof MultipleFormElement) {
            throw new Exception('Invalid form type for sub element.');
        }

        $this->subElement = $subElement;
    }

    /**
     * @return FormElement|null
     */
    public function getSubElement(): ?FormElement
    {
        return $this->subElement;
    }
}
