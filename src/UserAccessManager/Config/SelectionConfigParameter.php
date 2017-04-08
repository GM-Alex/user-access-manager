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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Config;

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
    protected $aSelections;

    /**
     * SelectionConfigParameter constructor.
     *
     * @param string $sId
     * @param mixed  $mDefaultValue
     * @param array  $aSelections
     */
    public function __construct($sId, $mDefaultValue, array $aSelections)
    {
        $this->aSelections = $aSelections;

        parent::__construct($sId, $mDefaultValue);
    }

    /**
     * Checks if the value is part of the selection.
     *
     * @param mixed $mValue
     *
     * @return bool
     */
    public function isValidValue($mValue)
    {
        $aMap = array_flip($this->aSelections);
        return (isset($aMap[$mValue]) === true);
    }

    /**
     * Returns the available selections.
     *
     * @return array
     */
    public function getSelections()
    {
        return $this->aSelections;
    }
}
