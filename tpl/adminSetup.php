<?php
/**
 * adminSetup.php
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

if (isset($_POST['action'])) {
    $post_action = $_POST['action'];
} else {
    $post_action = null;
}

if ($post_action == 'reset_uam') {
    if (isset($_POST['uam_reset'])) {
        $reset = $_POST['uam_reset'];
    } else {
        $reset = null;
    }
    
    if ($reset == 'true') {
        $userAccessManager = new UserAccessManager();
        $userAccessManager->uninstall();
        $userAccessManager->install();
        ?>
		<div class="updated">
			<p><strong><?php echo TXT_UAM_RESET_SUC; ?></strong></p>
		</div>
        <?php
    }
}
?>

<div class="wrap">
    <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
    	<input type="hidden" value="reset_uam" name="action" />
        <h2><?php echo TXT_SETUP; ?></h2>
        <table class="form-table">
        	<tbody>
        		<tr valign="top">
        			<th scope="row"><?php echo TXT_RESET_UAM; ?></th>
        			<td>
        				<label for="uam_reset_yes"> 
        					<input type="radio" id="uam_reset_yes" class="uam_reset_yes" name="uam_reset" value="true" /> 
        					<?php echo TXT_YES; ?> 
        				</label>&nbsp;&nbsp;&nbsp;&nbsp;
        				<label for="uam_reset_no"> 
        					<input type="radio" id="uam_reset_no" class="uam_reset_no" name="uam_reset" value="false" checked="checked" /> 
        					<?php echo TXT_NO; ?> 
        				</label>&nbsp;&nbsp;&nbsp;&nbsp;
        				<input type="submit" class="button" name="uam_reset_submit" value="<?php echo TXT_RESET; ?>" /> <br />
        				<p style="color: red; font-size: 12px; font-weight: bold;">
        				    <?php echo TXT_RESET_UAM_DESC; ?>
        				</p>
        			</td>
        		</tr>
        	</tbody>
        </table>
    </form>
</div>