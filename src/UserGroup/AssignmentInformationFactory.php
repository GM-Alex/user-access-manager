<?php
/**
 * AssignmentInformationFactory.php
 *
 * The AssignmentInformationFactory class file.
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
 * Class AssignmentInformationFactory
 *
 * @package UserAccessManager\UserGroup
 */
class AssignmentInformationFactory
{
    /**
     * Creates an AssignmentInformation object.
     * @param null|string $type
     * @param null|string $fromDate
     * @param null|string $toDate
     * @param array $recursiveMembership
     * @return AssignmentInformation
     */
    public function createAssignmentInformation(
        $type = null,
        $fromDate = null,
        $toDate = null,
        array $recursiveMembership = []
    ): AssignmentInformation {
        return new AssignmentInformation($type, $fromDate, $toDate, $recursiveMembership);
    }
}
