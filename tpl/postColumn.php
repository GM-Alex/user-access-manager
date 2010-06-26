<?php
/**
 * postColumn.php
 * 
 * Shows the setup page at the admin panel.
 * 
 * PHP versions 5
 * 
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2010 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

$uamAccessHandler = new UamAccessHandler();
$usergroups = $uamAccessHandler->getUsergroupsForPost($id);
if ($usergroups != Array()) {
    include 'groupInfo.php';
} else {
    echo TXT_FULL_ACCESS;
}
?>