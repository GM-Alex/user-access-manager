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
 * @copyright 2008-2016 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
global $oUserAccessManager;

$sPostAction = (isset($_POST['action'])) ? $_POST['action'] : null;

if ($sPostAction === 'update_db') {
    if (empty($_POST) || !wp_verify_nonce($_POST['uamSetupUpdateNonce'], 'uamSetupUpdate')) {
         wp_die(TXT_UAM_NONCE_FAILURE);
    }

    $sUpdate = isset($_POST['uam_update_db']) ? $_POST['uam_update_db'] : null;

    if ($sUpdate === 'blog' || $sUpdate === 'network' ) {
        $blNetwork = ($sUpdate == 'network') ? true : false;
        $oUserAccessManager->update($blNetwork);
        ?>
        <div class="updated">
            <p><strong><?php echo TXT_UAM_UAM_DB_UPDATE_SUC; ?></strong></p>
        </div>
        <?php
    }
} elseif ($sPostAction === 'reset_uam') {
    if (empty($_POST) || !wp_verify_nonce($_POST['uamSetupResetNonce'], 'uamSetupReset')) {
         wp_die(TXT_UAM_NONCE_FAILURE);
    }

    $sReset = (isset($_POST['uam_reset'])) ? $_POST['uam_reset'] : null;
    
    if ($sReset === 'reset') {
        $oUserAccessManager = new UserAccessManager();
        $oUserAccessManager->uninstall();
        $oUserAccessManager->install();
        ?>
        <div class="updated">
            <p><strong><?php echo TXT_UAM_UAM_RESET_SUCCSESS; ?></strong></p>
        </div>
        <?php
    }
}
?>

<div class="wrap"> 
    <h2><?php echo TXT_UAM_SETUP; ?></h2>
<?php
if ($oUserAccessManager->isDatabaseUpdateNecessary()) {
    $blShowNetworkUpdate = is_super_admin()
        && defined('MULTISITE')
        && defined('WP_ALLOW_MULTISITE')
        && WP_ALLOW_MULTISITE;

    ?>
    <table class="form-table">
        <tbody>
            <tr valign="top">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>">
                    <?php wp_nonce_field('uamSetupUpdate', 'uamSetupUpdateNonce'); ?>
                    <input type="hidden" value="update_db" name="action" />
                    <th scope="row"><?php echo TXT_UAM_UPDATE_UAM_DB; ?></th>
                    <td>
                        <?php
                        if ($blShowNetworkUpdate) {
                            ?>
                            <input type="radio" id="uam_update_db_network" class="uam_update_db_network" name="uam_update_db" value="network" />
                            <label for="uam_update_db_network"><?php echo TXT_UAM_UPDATE_NETWORK; ?></label>&nbsp;&nbsp;&nbsp;&nbsp;
                            <input type="radio" id="uam_update_db_blog" class="uam_update_db" name="uam_update_db" value="blog" checked="checked" />
                            <label for="uam_update_db_blog"> <?php echo TXT_UAM_UPDATE_BLOG; ?></label>
                            <?php
                        } else {
                            ?>
                            <input type="hidden" value="blog" name="uam_update_db" />
                            <?php
                        }
                        ?>
                        <input type="submit" class="button" name="uam_update_db_submit" value="<?php echo TXT_UAM_UPDATE; ?>" /> <br />
                        <p style="color: red; font-size: 12px; font-weight: bold;">
                            <?php echo TXT_UAM_UPDATE_UAM_DB_DESC; ?>
                        </p>
                    </td>
                </form>
            </tr>
        </tbody>
    </table>
    <?php
}
?>
    <h2 style="color:red;"><?php echo TXT_UAM_SETUP_DANGER_ZONE; ?></h2>
    <table class="form-table">
        <tbody>
            <tr valign="top">
                <th scope="row"><?php echo TXT_UAM_RESET_UAM; ?></th>
                <td>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>">
                        <?php wp_nonce_field('uamSetupReset', 'uamSetupResetNonce'); ?>
                        <input type="hidden" value="reset_uam" name="action" />
                        <input id="uam_reset_confirm" class="uam_reset_confirm" name="uam_reset" />
                        <input id="uam_reset_submit" disabled="disabled" type="submit" class="button" name="uam_reset_submit" value="<?php echo TXT_UAM_RESET; ?>" /> <br />
                        <p style="font-size: 12px; font-weight: bold;">
                            <?php echo TXT_UAM_RESET_UAM_DESC; ?>
                        </p>
                        <p style="color: red; font-size: 12px; font-weight: bold;">
                            <?php echo TXT_UAM_RESET_UAM_DESC_WARNING; ?>
                        </p>
                    </form>
                </td>
            </tr>
        </tbody>
    </table>
</div>