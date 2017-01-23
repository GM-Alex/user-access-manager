<?php
/**
 * ObjectColumn.php
 *
 * Shows the object column at the admin panel.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
/**
 * @var \UserAccessManager\Controller\AdminObjectController $this
 */
$aObjectUserGroups = $this->getObjectUserGroups();

if (count($aObjectUserGroups) > 0) {
    ?>
    <ul>
        <?php
        foreach ($aObjectUserGroups as $oUserGroup) {
            ?>
            <li>
                <a class="uam_group_info_link">
                    <?php echo htmlentities($oUserGroup->getGroupName()); ?>
                </a>
                <?php
                include 'GroupInfo.php';
                ?>
            </li>
            <?php
        }
        ?>
    </ul>
    <?php
} elseif ($this->getUserGroupDiff() > 0) {
    echo TXT_UAM_MEMBER_OF_OTHER_GROUPS;
} else {
    echo TXT_UAM_FULL_ACCESS;
}