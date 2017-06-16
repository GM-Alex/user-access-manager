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
        $currentSectionKey = $controller->getCurrentSettingsSection();
        $groupForms = $controller->getCurrentGroupForms();

        if (count($groupForms) > 1) {
            ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="uam_settings_group_section">
                                <?php echo $controller->getText($currentGroupKey.'_SECTION_SELECTION'); ?>
                            </label>
                        </th>
                        <td>
                            <select id="uam_settings_group_section" name="section">
                                <?php
                                foreach ($groupForms as $sectionKey => $form) {
                                    ?>
                                    <option value="<?php echo $sectionKey?>"
                                            data-link="<?php
                                            echo $controller->getSectionGroupLink($currentGroupKey, $sectionKey);
                                            ?>"
                                        <?php
                                        if ($currentSectionKey === $sectionKey) {
                                            echo 'selected="selected"';
                                        }
                                        ?>><?php
                                            echo ($sectionKey === \UserAccessManager\Config\MainConfig::DEFAULT_TYPE) ?
                                                TXT_UAM_SETTINGS_GROUP_SECTION_DEFAULT :
                                                $controller->getObjectName($sectionKey);
                                        ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
        }

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