<?php
/**
 * UserGroupList.php
 *
 * Shows the user group edit list at the admin panel.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

/**
 * @var UserGroupController $controller
 */

/**
 * @param string $name
 * @param string $sortingParameter
 *
 * @return string
 */

use UserAccessManager\Controller\Backend\UserGroupController;
use UserAccessManager\Object\ObjectHandler;

$createHeaderColumn = function ($name, $sortingParameter) use ($controller) {
    $isSorted = $controller->getRequestParameter('orderby') === $sortingParameter;
    $class = $isSorted === true ? 'sorted' : 'sortable';
    $sortOrder = $isSorted === true ? $controller->getRequestParameter('order') : 'asc';

    $inner = "<span>{$name}</span><span class=\"sorting-indicator\"></span>";
    $link = "<a href=\"{$controller->getSortUrl($sortingParameter)}\" >{$inner}</a>";
    return "<th scope=\"col\" class=\"{$class} {$sortOrder}\">{$link}</th>";
}

?>
<form method="post" action="<?php echo $controller->getRequestUrl(); ?>">
    <?php $controller->createNonceField('uamDeleteGroup'); ?>
    <input type="hidden" value="delete_user_group" name="uam_action"/>
    <div class="tablenav">
        <div class="alignleft">
            <input type="submit" class="button-secondary delete" name="deleteit"
                   value="<?php echo TXT_UAM_DELETE; ?>"/>
        </div>
        <br class="clear"/>
    </div>
    <table class="widefat">
        <thead>
        <tr class="thead">
            <th scope="col"></th>
            <?php echo $createHeaderColumn(TXT_UAM_NAME, 'name'); ?>
            <?php echo $createHeaderColumn(TXT_UAM_DESCRIPTION, 'description'); ?>
            <?php echo $createHeaderColumn(TXT_UAM_READ_ACCESS, 'readAccess'); ?>
            <?php echo $createHeaderColumn(TXT_UAM_WRITE_ACCESS, 'writeAccess'); ?>
            <th scope="col"><?php echo TXT_UAM_GROUP_ROLE; ?></th>
            <?php echo $createHeaderColumn(TXT_UAM_IP_RANGE, 'ipRange'); ?>
        </tr>
        </thead>
        <tbody>
        <?php
        $currentAdminPage = $controller->getRequestParameter('page');
        $userGroups = $controller->getUserGroups();
        foreach ($userGroups as $userGroup) {
            ?>
            <tr class="alternate" id="group-<?php echo $userGroup->getId(); ?>">
                <th class="check-column">
                    <label>
                        <input type="checkbox" value="<?php echo $userGroup->getId(); ?>" name="delete[]"/>
                    </label>
                </th>
                <td>
                    <strong>
                        <?php
                        $link = "?page={$currentAdminPage}&amp;uam_action=edit_user_group"
                            . "&amp;userGroupId={$userGroup->getId()}";
                        ?>
                        <a href="<?php echo $link; ?>">
                            <?php echo htmlentities($userGroup->getName()); ?>
                        </a>
                    </strong>
                </td>
                <td><?php echo htmlentities($userGroup->getDescription()) ?></td>
                <td>
                    <?php
                    if ($userGroup->getReadAccess() === 'all') {
                        echo TXT_UAM_ALL;
                    } elseif ($userGroup->getReadAccess() === 'group') {
                        echo TXT_UAM_ONLY_GROUP_USERS;
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if ($userGroup->getWriteAccess() === 'all') {
                        echo TXT_UAM_ALL;
                    } elseif ($userGroup->getWriteAccess() === 'group') {
                        echo TXT_UAM_ONLY_GROUP_USERS;
                    } elseif ($userGroup->getWriteAccess() === 'none') {
                        echo TXT_UAM_NONE;
                    }
                    ?>
                </td>
                <td>
                    <?php
                    $roleNames = $controller->getRoleNames();
                    $groupRoles = $userGroup->getAssignedObjectsByType(
                        ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                    );

                    if (count($groupRoles) > 0) {
                        ?>
                        <ul style="margin: 0;">
                            <?php
                            foreach ($groupRoles as $key => $role) {
                                ?>
                                <li><?php echo isset($roleNames[$key]) ? $roleNames[$key] : $key; ?></li>
                                <?php
                            }
                            ?>
                        </ul>
                        <?php
                    } else {
                        echo TXT_UAM_NONE;
                    }
                    ?>
                </td>
                <td>
                    <?php
                    $ipRanges = $userGroup->getIpRangeArray();

                    if (count($ipRanges) > 0) {
                        ?>
                        <ul>
                            <?php
                            foreach ($ipRanges as $ipRange) {
                                ?>
                                <li><?php echo htmlentities($ipRange); ?></li>
                                <?php
                            }
                            ?>
                        </ul>
                        <?php
                    } else {
                        echo TXT_UAM_NONE;
                    }
                    ?>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
    <div class="tablenav">
        <div class="alignleft">
            <input type="submit"
                   class="button-secondary delete"
                   name="deleteit"
                   value="<?php echo TXT_UAM_DELETE; ?>"/>
        </div>
        <br class="clear"/>
    </div>
</form>
