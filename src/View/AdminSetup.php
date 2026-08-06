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
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

/**
 * @var UserAccessManager\Controller\Backend\Administration\SetupController $controller
 */
if ($controller->hasUpdateMessage()) {
    ?>
    <div class="notice notice-success">
        <p><strong><?php echo $controller->getUpdateMessage(); ?></strong></p>
    </div>
    <?php
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo TXT_UAM_SETUP; ?></h1>
    <div class="uam_sidebar">
        <?php include 'InfoBox.php'; ?>
    </div>
    <div class="uam_main">
        <?php
        if ($controller->isDatabaseUpdateNecessary() === true) {
            ?>
            <table class="form-table">
                <tbody>
                <tr valign="top">
                    <th scope="row"><?php echo TXT_UAM_UPDATE_UAM_DB; ?></th>
                    <td>
                        <form class="uam_setup_form" method="post" action="<?php echo $controller->getRequestUrl(); ?>">
                            <?php $controller->createNonceField('uamSetupUpdate'); ?>
                            <input type="hidden" value="update_database" name="uam_action"/>
                            <?php
                            if ($controller->showNetworkUpdate() === true) {
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
                            &nbsp;<input type="submit" class="button"
                                         name="uam_update_db_submit"
                                         value="<?php echo TXT_UAM_UPDATE; ?>"/>
                            <p>
                                <input type="checkbox"
                                       id="uam_backup_db"
                                       checked="checked"
                                       name="uam_backup_db" value="1">
                                <label for="uam_backup_db"><?php echo TXT_UAM_UPDATE_BACKUP; ?></label>
                            </p>
                            <div class="notice notice-warning inline">
                                <p><?php echo TXT_UAM_UPDATE_UAM_DB_DESCRIPTION; ?></p>
                            </div>
                        </form>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
        }

        $isDatabaseBroken = $controller->isDatabaseBroken();
        $databaseNoticeType = ($isDatabaseBroken === true) ? 'error' : 'success';
        $databaseNoticeText = ($isDatabaseBroken === true) ? TXT_UAM_DATABASE_BROKEN : TXT_UAM_DATABASE_OK;
        ?>
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row"><?php echo TXT_UAM_REPAIR_DATABASE; ?></th>
                <td>
                    <div class="notice notice-<?php echo $databaseNoticeType; ?> inline">
                        <p><?php echo $databaseNoticeText; ?></p>
                    </div>
                    <?php
                    if ($isDatabaseBroken === true) {
                        ?>
                        <form class="uam_setup_form" method="post" action="<?php echo $controller->getRequestUrl(); ?>">
                            <?php $controller->createNonceField('uamSetupRepair'); ?>
                            <input type="hidden" value="repair_database" name="uam_action"/>
                            <input type="submit" class="button"
                                   name="uam_repair_db_submit"
                                   value="<?php echo TXT_UAM_REPAIR_DATABASE_REPAIR_NOW; ?>"/>
                            <p class="description">
                                <?php echo TXT_UAM_REPAIR_DATABASE_DESCRIPTION; ?>
                            </p>
                        </form>
                        <?php
                    }
                    ?>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
        $backups = $controller->getBackups();

        if (count($backups) > 0) {
            ?>
            <table class="form-table">
                <tbody>
                <tr valign="top">
                    <th scope="row"><?php echo TXT_UAM_REVERT_DATABASE; ?></th>
                    <td>
                        <form class="uam_setup_form" method="post" action="<?php echo $controller->getRequestUrl(); ?>">
                            <?php $controller->createNonceField('uamSetupRevert'); ?>
                            <input type="hidden" value="revert_database" name="uam_action"/>
                            <?php
                            foreach ($backups as $backup) {
                                ?>
                                <input type="radio"
                                       id="uam_revert_backup_<?php echo $backup; ?>"
                                       name="uam_revert_database"
                                       value="<?php echo $backup; ?>">
                                <label for="uam_revert_backup_<?php echo $backup; ?>"><?php echo $backup; ?></label>
                                <?php
                            }
                            ?>
                            &nbsp;<input type="submit" class="button"
                                         name="uam_revert_db_submit"
                                         value="<?php echo TXT_UAM_REVERT_DATABASE_REVERT_NOW; ?>"/>
                            <p class="description">
                                <?php echo TXT_UAM_REVERT_DATABASE_DESCRIPTION; ?>
                            </p>
                        </form>
                    </td>
                </tr>
                </tbody>
            </table>
            <table class="form-table">
                <tbody>
                <tr valign="top">
                    <th scope="row"><?php echo TXT_UAM_DELETE_DATABASE_BACKUP; ?></th>
                    <td>
                        <form class="uam_setup_form" method="post" action="<?php echo $controller->getRequestUrl(); ?>">
                            <?php $controller->createNonceField('uamSetupDeleteBackup'); ?>
                            <input type="hidden" value="delete_database_backup" name="uam_action"/>
                            <?php
                            foreach ($backups as $backup) {
                                ?>
                                <input type="radio"
                                       id="uam_delete_backup_<?php echo $backup; ?>"
                                       name="uam_delete_backup"
                                       value="<?php echo $backup; ?>">
                                <label for="uam_delete_backup_<?php echo $backup; ?>"><?php echo $backup; ?></label>
                                <?php
                            }
                            ?>
                            &nbsp;<input type="submit" class="button"
                                         name="uam_delete_db_backup_submit"
                                         value="<?php echo TXT_UAM_DELETE_DATABASE_BACKUP_DELETE_NOW; ?>"/>
                            <p class="description">
                                <?php echo TXT_UAM_DELETE_DATABASE_BACKUP_DESCRIPTION; ?>
                            </p>
                        </form>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
        }
        ?>
        <h2 class="uam_danger_zone"><?php echo TXT_UAM_SETUP_DANGER_ZONE; ?></h2>
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row"><label for="uam_reset_confirm"><?php echo TXT_UAM_RESET_UAM; ?></label></th>
                <td>
                    <form class="uam_setup_form" method="post" action="<?php echo $controller->getRequestUrl(); ?>">
                        <?php $controller->createNonceField('uamSetupReset'); ?>
                        <input type="hidden" value="reset_uam" name="uam_action"/>
                        <input id="uam_reset_confirm" type="text" class="uam_reset_confirm" name="uam_reset"/>
                        <input id="uam_reset_submit"
                               disabled="disabled"
                               type="submit"
                               class="button"
                               name="uam_reset_submit"
                               value="<?php echo TXT_UAM_RESET; ?>"
                        />
                        <br/>
                        <p class="description">
                            <?php echo TXT_UAM_RESET_UAM_DESCRIPTION; ?>
                        </p>
                        <div class="notice notice-error inline">
                            <p><?php echo TXT_UAM_RESET_UAM_DESC_WARNING; ?></p>
                        </div>
                    </form>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>