<?php
/**
 * ValueSetFormElement.php
 *
 * The ValueSetFormElement class file.
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
 * Class ValueSetFormElement
 *
 * @package UserAccessManager\Form
 */
abstract class ValueSetFormElement extends FormElement
{
    /**
     * @var ValueSetFormElementValue[]
     */
    protected $possibleValues;

    /**
     * MultipleFormElement constructor.
     * @param string $id
     * @param ValueSetFormElementValue[] $possibleValues
     * @param mixed|null $value
     * @param string|null $label
     * @param string|null $description
     */
    public function __construct(string $id, array $possibleValues, $value = null, $label = null, $description = null)
    {
        parent::__construct($id, $value, $label, $description);

        $keys = array_map(
            function (ValueSetFormElementValue $value) {
                return $value->getValue();
            },
            $possibleValues
        );

        $this->possibleValues = array_combine($keys, $possibleValues);
    }

    /**
     * @return ValueSetFormElementValue[]
     */
    public function getPossibleValues(): array
    {
        return $this->possibleValues;
    }
}
