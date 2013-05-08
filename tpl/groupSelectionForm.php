<?php
/**
 * groupSelectionForm.php
 * 
 * Shows the selection form.
 * 
 * PHP versions 5
 * 
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2013 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
?>
<input type="hidden" name="uam_update_groups" value="true" />
<ul class="uam_group_selection">
<?php
if (!isset($sGroupsFormName)
    || $sGroupsFormName === null
) {
    $sGroupsFormName = 'uam_usergroups';
}

foreach ($aUamUserGroups as $oUamUserGroup) {
    $sAddition = '';
    $sAttributes = '';
    
    if (array_key_exists($oUamUserGroup->getId(), $aUserGroupsForObject)) {
        $sAttributes .= 'checked="checked" ';
    }
    
    if (isset($aUserGroupsForObject[$oUamUserGroup->getId()]->aSetRecursive[$sObjectType][$iObjectId])) {
        $sAttributes .= 'disabled="" ';
		$sAddition .= ' [LR]';
	}
    
	?>
	<li>
		<label for="<?php echo $sGroupsFormName; ?>-<?php echo $oUamUserGroup->getId(); ?>" class="selectit" style="display:inline;" >
			<input type="checkbox" id="<?php echo $sGroupsFormName; ?>-<?php echo $oUamUserGroup->getId(); ?>" <?php echo $sAttributes;?> value="<?php echo $oUamUserGroup->getId(); ?>" name="<?php echo $sGroupsFormName; ?>[]" />
			<?php echo $oUamUserGroup->getGroupName().$sAddition; ?>
		</label>
		<a class="uam_group_info_link">(<?php echo TXT_UAM_INFO; ?>)</a>
		<?php include 'groupInfo.php'; ?>
	</li>
	<?php
}
?>
</ul>