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
 * @var \UserAccessManager\Controller\Backend\SettingsController $controller
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
    <h2><?php echo TXT_UAM_SETTINGS; ?></h2>
    <div class="uam_sidebar">
        <?php include 'InfoBox.php'; ?>
    </div>
    <div class="uam_main">
        <?php include 'TabList.php'; ?>
        <form method="post" action="<?php echo $controller->getRequestUrl(); ?>">
            <?php $controller->createNonceField('uamUpdateSettings'); ?>
            <input type="hidden" name="uam_action" value="update_settings"/>
            <?php
            $currentSectionKey = $controller->getCurrentTabGroupSection();
            $groupForms = $controller->getCurrentGroupForms();
            $form = isset($groupForms[$currentSectionKey]) ? $groupForms[$currentSectionKey] : reset($groupForms);
            $cssClass = ($currentGroupKey === 'post_types') ? " uam_settings_group_post_type $currentSectionKey" : '';
            $cssClass .= ($currentGroupKey === 'taxonomies') ? " uam_settings_group_taxonomies $currentSectionKey" : '';
            ?>
            <h3><?php echo $controller->getText($currentSectionKey); ?></h3>
            <p><?php echo $controller->getText($currentSectionKey, true); ?></p>
            <table id="uam_settings_group_<?php echo $currentSectionKey; ?>"
                   class="form-table<?php echo $cssClass; ?>">
                <tbody>
                <?php
                if ($form instanceof \UserAccessManager\Form\Form) {
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
                } elseif (is_string($form) === true) {
                    echo $form;
                }
                ?>
                </tbody>
            </table>
            <div class="submit">
                <input type="submit" value="<?php echo TXT_UAM_UPDATE_SETTING; ?>"/>
            </div>
        </form>
    </div>
</div>