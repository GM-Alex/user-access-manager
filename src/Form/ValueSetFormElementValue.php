<?php
/**
 * ValueSetFormElementValue.php
 *
 * The ValueSetFormElementValue class file.
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
 * Class ValueSetFormElementValue
 *
 * @package UserAccessManager\Form
 */
class ValueSetFormElementValue
{
    use ValueTrait;
    use LabelTrait;

    /**
     * ValueSetFormElementValue constructor.
     *
     * @param mixed  $value
     * @param string $label
     */
    public function __construct($value, $label)
    {
        $this->value = $value;
        $this->label = $label;
    }

    /**
     * @var bool
     */
    private $isDisabled = false;

    /**
     * Marks the option as disabled.
     */
    public function markDisabled()
    {
        $this->isDisabled = true;
    }

    /**
     * Returns true if the option is disabled.
     *
     * @return bool
     */
    public function isDisabled()
    {
        return $this->isDisabled;
    }
}
