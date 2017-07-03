<?php
/**
 * AssignmentInformation.php
 *
 * The AssignmentInformation class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\UserGroup;

/**
 * Class AssignmentInformation
 *
 * @package UserAccessManager\UserGroup
 */
class AssignmentInformation
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var null|string
     */
    private $fromDate;

    /**
     * @var null|string
     */
    private $toDate;

    /**
     * AssignmentInformation constructor.
     *
     * @param string $type
     * @param string $fromDate
     * @param string $toDate
     */
    public function __construct($type, $fromDate = null, $toDate = null)
    {
        $this->type = $type;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return null|string
     */
    public function getFromDate()
    {
        return $this->fromDate;
    }

    /**
     * @return null|string
     */
    public function getToDate()
    {
        return $this->toDate;
    }
}
