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

declare(strict_types=1);

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
     * @var array
     */
    private $recursiveMembership;

    /**
     * AssignmentInformation constructor.
     * @param string $type
     * @param string $fromDate
     * @param string $toDate
     * @param array $recursiveMembership
     */
    public function __construct($type = null, $fromDate = null, $toDate = null, array $recursiveMembership = [])
    {
        $this->type = $type;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->recursiveMembership = $recursiveMembership;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return null|string
     */
    public function getFromDate(): ?string
    {
        return $this->fromDate;
    }

    /**
     * @return null|string
     */
    public function getToDate(): ?string
    {
        return $this->toDate;
    }

    /**
     * @param array $recursiveMembership
     */
    public function setRecursiveMembership(array $recursiveMembership)
    {
        $this->recursiveMembership = $recursiveMembership;
    }

    /**
     * @return array
     */
    public function getRecursiveMembership(): array
    {
        return $this->recursiveMembership;
    }
}
