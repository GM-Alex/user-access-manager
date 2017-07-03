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
namespace UserAccessManager\UserGroup;

/**
 * Class AssignmentInformationFactory
 *
 * @package UserAccessManager\UserGroup
 */
class AssignmentInformationFactory
{
    public function createAssignmentInformation($type, $fromDate = null, $toDate = null)
    {
        return new AssignmentInformation($type, $fromDate, $toDate);
    }
}
