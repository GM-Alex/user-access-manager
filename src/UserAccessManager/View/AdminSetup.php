<?php
/**
 * AdminSetup.php
 *
 * Shows the setup page at the admin panel.
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
 * @var UserAccessManager\Controller\AdminSetupController $oController
 */
if ($oController->hasUpdateMessage()) {
    ?>
    <div class="updated">
        <p><strong><?php echo $oController->getUpdateMessage(); ?></strong></p>
    </div>
    <?php
}
?>
<div class="wrap">
    <h2><?php echo TXT_UAM_SETUP; ?></h2>
    <?php
    if ($oController->isDatabaseUpdateNecessary()) {
        ?>
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row"><?php echo TXT_UAM_UPDATE_UAM_DB; ?></th>
                <td>
                    <form method="post" action="<?php echo $oController->getRequestUrl(); ?>">
                        <?php $oController->createNonceField('uamSetupUpdate'); ?>
                        <input type="hidden" value="update_database" name="uam_action"/>
                        <?php
                        if ($oController->showNetworkUpdate() === true) {
                            ?>
                            <input type="radio" id="uam_update_db_network" class="uam_update_db_network"
                                   name="uam_update_db" value="network"/>
                            <label for="uam_update_db_network"><?php echo TXT_UAM_UPDATE_NETWORK; ?></label>
                            <input type="radio" id="uam_update_db_blog" class="uam_update_db" name="uam_update_db"
                                   value="blog" checked="checked"/>
                            <label for="uam_update_db_blog"> <?php echo TXT_UAM_UPDATE_BLOG; ?></label>
                            <?php
                        } else {
                            ?>
                            <input type="hidden" value="blog" name="uam_update_db"/>
                            <?php
                        }
                        ?>
                        <input type="submit" class="button" name="uam_update_db_submit"
                               value="<?php echo TXT_UAM_UPDATE; ?>"/> <br/>
                        <p style="color: red; font-size: 12px; font-weight: bold;">
                            <?php echo TXT_UAM_UPDATE_UAM_DB_DESC; ?>
                        </p>
                    </form>
                </td>
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
            <th scope="row"><label for="uam_reset_confirm"><?php echo TXT_UAM_RESET_UAM; ?></label></th>
            <td>
                <form method="post" action="<?php echo $oController->getRequestUrl(); ?>">
                    <?php $oController->createNonceField('uamSetupReset'); ?>
                    <input type="hidden" value="reset_uam" name="uam_action"/>
                    <input id="uam_reset_confirm" class="uam_reset_confirm" name="uam_reset"/>
                    <input id="uam_reset_submit"
                           disabled="disabled"
                           type="submit"
                           class="button"
                           name="uam_reset_submit"
                           value="<?php echo TXT_UAM_RESET; ?>"
                    />
                    <br/>
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