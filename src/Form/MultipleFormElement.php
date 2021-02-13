<?php
/**
 * MultipleFormElement.php
 *
 * The MultipleFormElement class file.
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
 * Class MultipleFormElement
 *
 * @package UserAccessManager\Form
 */
abstract class MultipleFormElement extends ValueSetFormElement
{
    /**
     * @var MultipleFormElementValue[]
     */
    protected $possibleValues;

    /**
     * MultipleFormElement constructor.
     * @param string $id
     * @param MultipleFormElementValue[] $possibleValues
     * @param mixed|null $value
     * @param string|null $label
     * @param string|null $description
     * @throws Exception
     */
    public function __construct(string $id, array $possibleValues, $value = null, $label = null, $description = null)
    {
        foreach ($possibleValues as $possibleValue) {
            if (($possibleValue instanceof MultipleFormElementValue) === false) {
                throw new Exception('Values must be MultipleFormElementValue objects');
            }
        }

        parent::__construct($id, $possibleValues, $value, $label, $description);
    }

    /**
     * @return MultipleFormElementValue[]
     */
    public function getPossibleValues(): array
    {
        return $this->possibleValues;
    }
}
