<?php
/**
 * AdminSettings.php
 *
 * Shows the settings page at the admin panel.
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
 * @var \UserAccessManager\Controller\AdminSettingsController $controller
 */
if ($controller->hasUpdateMessage()) {
    ?>
    <div class="updated">
        <p><strong><?php echo $controller->getUpdateMessage(); ?></strong></p>
    </div>
    <?php
}

?>
<div class="wrap">
    <form method="post" action="<?php echo $controller->getRequestUrl(); ?>">
        <?php $controller->createNonceField('uamUpdateSettings'); ?>
        <input type="hidden" name="uam_action" value="update_settings"/>
        <h2><?php echo TXT_UAM_SETTINGS; ?></h2>
        <h2 class="nav-tab-wrapper">
            <?php
            $currentGroupKey = $controller->getCurrentSettingsGroup();
            $settingGroups = $controller->getSettingsGroups();

            foreach ($settingGroups as $group) {
                $cssClass = 'nav-tab';

                if ($currentGroupKey === $group) {
                    $cssClass .= ' nav-tab-active';
                }

                ?>
                <a class="<?php  echo $cssClass; ?>"
                   href="<?php echo $controller->getSettingsGroupLink($group); ?>">
                    <?php echo $controller->getText($group); ?>
                </a>
                <?php
            }
            ?>
        </h2>
        <?php
        $groupForms = $controller->getCurrentGroupForms();

        foreach ($groupForms as $sectionKey => $form) {
            $cssClass = $controller->isPostTypeGroup($sectionKey) ? ' uam_settings_group_post_type' : '';
            ?>
            <h3><?php echo $controller->getText($sectionKey); ?></h3>
            <p><?php echo $controller->getText($sectionKey, true); ?></p>
            <table id="uam_settings_group_<?php echo $sectionKey; ?>"
                   class="form-table<?php echo $cssClass; ?>">
                <tbody>
                <?php
                $formElements = $form->getElements();

                foreach ($formElements as $formElement) {
                    ?>
                    <tr valign="top">
                        <?php
                        if ($formElement instanceof \UserAccessManager\Form\Input) {
                            $input = $formElement;
                            include 'AdminForm/Input.php';
                        } elseif ($formElement instanceof \UserAccessManager\Form\Textarea) {
                            $textarea = $formElement;
                            include 'AdminForm/Textarea.php';
                        } elseif ($formElement instanceof \UserAccessManager\Form\Select) {
                            $select = $formElement;
                            include 'AdminForm/Select.php';
                        } elseif ($formElement instanceof \UserAccessManager\Form\Radio) {
                            $radio = $formElement;
                            include 'AdminForm/Radio.php';
                        }
                        ?>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <?php
        }
        ?>
        <div class="submit">
            <input type="submit" value="<?php echo TXT_UAM_UPDATE_SETTING; ?>"/>
        </div>
    </form>
</div>