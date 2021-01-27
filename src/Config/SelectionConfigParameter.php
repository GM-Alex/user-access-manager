<?php
/**
 * SelectionConfigParameter.php
 *
 * The SelectionConfigParameter class file.
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

namespace UserAccessManager\Config;

use Exception;

/**
 * Class SelectionConfigParameter
 *
 * @package UserAccessManager\Config
 */
class SelectionConfigParameter extends ConfigParameter
{
    /**
     * @var array
     */
    private $selections;

    /**
     * SelectionConfigParameter constructor.
     * @param string $id
     * @param mixed $defaultValue
     * @param array $selections
     * @throws Exception
     */
    public function __construct(string $id, $defaultValue, array $selections)
    {
        $this->selections = $selections;

        parent::__construct($id, $defaultValue);
    }

    /**
     * Checks if the value is part of the selection.
     * @param mixed $value
     * @return bool
     */
    public function isValidValue($value): bool
    {
        $map = array_flip($this->selections);
        return (isset($map[$value]) === true);
    }

    /**
     * Returns the available selections.
     * @return array
     */
    public function getSelections(): array
    {
        return $this->selections;
    }
}
