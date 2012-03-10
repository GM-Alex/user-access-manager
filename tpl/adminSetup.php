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
global $userAccessManager;

if (isset($_POST['action'])) {
    $postAction = $_POST['action'];
} else {
    $postAction = null;
}

if ($postAction == 'update_db') {
    if (empty($_POST) 
        || !wp_verify_nonce($_POST['uamSetupUpdateNonce'], 'uamSetupUpdate')
    ) {
         wp_die(TXT_UAM_NONCE_FAILURE);
    }
    
    if (isset($_POST['uam_update_db'])) {
        $update = $_POST['uam_update_db'];
    } else {
        $update = null;
    }
    
    if ($update == 'true'
    	|| $update == 'network'
    ) {
        $network = false;
        
        if ($update == 'network') {
            $network = true;
        }
        
        $userAccessManager->update($network);
        ?>
    	<div class="updated">
    		<p><strong><?php echo TXT_UAM_UAM_DB_UPDATE_SUC; ?></strong></p>
    	</div>
    	<?php
    }
}

if ($postAction == 'reset_uam') {
    if (empty($_POST) 
        || !wp_verify_nonce($_POST['uamSetupResetNonce'], 'uamSetupReset')
    ) {
         wp_die(TXT_UAM_NONCE_FAILURE);
    }
    
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
			<p><strong><?php echo TXT_UAM_UAM_RESET_SUC; ?></strong></p>
		</div>
        <?php
    }
}
?>

<div class="wrap"> 
    <h2><?php echo TXT_UAM_SETUP; ?></h2>
    <table class="form-table">
    	<tbody>
    		<tr valign="top">
    			<th scope="row"><?php echo TXT_UAM_RESET_UAM; ?></th>
    			<td>
        			<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                        <?php wp_nonce_field('uamSetupReset', 'uamSetupResetNonce'); ?>
    					<input type="hidden" value="reset_uam" name="action" />
        				<label for="uam_reset_yes"> 
        					<input type="radio" id="uam_reset_yes" class="uam_reset_yes" name="uam_reset" value="true" /> 
        					<?php echo TXT_UAM_YES; ?> 
        				</label>&nbsp;&nbsp;&nbsp;&nbsp;
        				<label for="uam_reset_no"> 
        					<input type="radio" id="uam_reset_no" class="uam_reset_no" name="uam_reset" value="false" checked="checked" /> 
        					<?php echo TXT_UAM_NO; ?> 
        				</label>&nbsp;&nbsp;&nbsp;&nbsp;
        				<input type="submit" class="button" name="uam_reset_submit" value="<?php echo TXT_UAM_RESET; ?>" /> <br />
        				<p style="color: red; font-size: 12px; font-weight: bold;">
        				    <?php echo TXT_UAM_RESET_UAM_DESC; ?>
        				</p>
        			</form>
    			</td>
    		</tr>
    		<?php 
if ($userAccessManager->isDatabaseUpdateNecessary()) {
        		?>
        		<tr valign="top">
        			<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
                        <?php wp_nonce_field('uamSetupUpdate', 'uamSetupUpdateNonce'); ?>
    					<input type="hidden" value="update_db" name="action" />
            			<th scope="row"><?php echo TXT_UAM_UPDATE_UAM_DB; ?></th>
            			<td>
	<?php 
    if (is_super_admin()) {
	    ?>
            				<label for="uam_update_db_yes"> 
            					<input type="radio" id="uam_update_db_yes" class="uam_reset_yes" name="uam_update_db" value="network" /> 
            					<?php echo TXT_UAM_UPDATE_NETWORK; ?> 
            				</label>&nbsp;&nbsp;&nbsp;&nbsp;
	    <?php
    }
	?>
            				<label for="uam_update_db_yes"> 
            					<input type="radio" id="uam_update_db_yes" class="uam_reset_yes" name="uam_update_db" value="true" /> 
	<?php 
	if (is_super_admin()) { 
	    echo TXT_UAM_UPDATE_BLOG; 
	} else { 
	    echo TXT_UAM_YES; 
	} 
	?> 
            				</label>&nbsp;&nbsp;&nbsp;&nbsp;
            				<label for="uam_update_db_no"> 
            					<input type="radio" id="uam_update_db_no" class="uam_reset_no" name="uam_update_db" value="false" checked="checked" /> 
            					<?php echo TXT_UAM_NO; ?> 
            				</label>&nbsp;&nbsp;&nbsp;&nbsp;
            				<input type="submit" class="button" name="uam_update_db_submit" value="<?php echo TXT_UAM_UPDATE; ?>" /> <br />
            				<p style="color: red; font-size: 12px; font-weight: bold;">
            				    <?php echo TXT_UAM_UPDATE_UAM_DB_DESC; ?>
            				</p>
            			</td>
            		</form>
        		</tr>
        		<?php 
}
        	?>
    	</tbody>
    </table>
</div>