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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

/**
 * @var \UserAccessManager\Controller\AdminSettingsController $this
 */
if ($this->hasUpdateMessage()) {
    ?>
    <div class="updated">
        <p><strong><?php echo $this->getUpdateMessage(); ?></strong></p>
    </div>
    <?php
}

?>
<div class="wrap">
    <form method="post" action="<?php echo $this->getRequestUrl(); ?>">
        <?php $this->createNonceField('uamUpdateSettings'); ?>
        <input type="hidden" name="uam_action" value="update_settings"/>
        <h2><?php echo TXT_UAM_SETTINGS; ?></h2>
        <?php
        $aGroupedConfigParameters = $this->getGroupedConfigParameters();

        /**
         * @var \UserAccessManager\Config\ConfigParameter[] $aGroupParameters
         */
        foreach ($aGroupedConfigParameters as $sGroupKey => $aGroupParameters) {
            ?>
            <h3><?php echo constant('TXT_UAM_'.strtoupper($sGroupKey).'_SETTING'); ?></h3>
            <p><?php echo constant('TXT_UAM_'.strtoupper($sGroupKey).'_SETTING_DESC'); ?></p>
            <table id="uam_settings_group_<?php echo $sGroupKey; ?>" class="form-table">
                <tbody>
                <?php
                $aConfigParameters = $this->getConfigParameters();

                foreach ($aGroupParameters as $oGroupParameter) {
                    ?>
                    <tr valign="top">
                        <?php
                        if ($oGroupParameter->getId() === 'lock_file_types') {
                            ?>
                            <th><?php
                                echo TXT_UAM_LOCK_FILE_TYPES; ?></th>
                            <td>
                                <label for="uam_lock_file_types_all">
                                    <input type="radio" id="uam_lock_file_types_all"
                                           name="config_parameters[<?php echo $oGroupParameter->getId(); ?>]"
                                           value="all" <?php
                                    if ($oGroupParameter->getValue() === 'all'
                                        || $this->isNginx() && $oGroupParameter->getValue() === 'not_selected'
                                    ) {
                                        echo 'checked="checked"';
                                    } ?> />
                                    <?php echo TXT_UAM_ALL; ?>
                                </label>&nbsp;&nbsp;&nbsp;
                                <label for="uam_lock_file_types_selected">
                                    <input type="radio" id="uam_lock_file_types_selected"
                                           name="config_parameters[<?php echo $oGroupParameter->getId(); ?>]"
                                           value="selected" <?php
                                    if ($oGroupParameter->getValue() === 'selected') {
                                        echo 'checked="checked"';
                                    }
                                    ?> />
                                    <?php echo TXT_UAM_LOCKED_FILE_TYPES; ?>
                                    <input name="config_parameters[<?php echo $aConfigParameters['locked_file_types']->getId(); ?>]"
                                           value="<?php echo $aConfigParameters['locked_file_types']->getValue(); ?>"/>
                                </label>
                                &nbsp;&nbsp;&nbsp;
                                <?php
                                if ($this->isNginx() === false) {
                                    ?>
                                    <label for="uam_lock_file_types_not_selected">
                                        <input type="radio" id="uam_lock_file_types_not_selected"
                                               name="config_parameters[<?php echo $oGroupParameter->getId(); ?>]"
                                               value="not_selected" <?php
                                        if ($oGroupParameter->getValue() === 'not_selected') {
                                            echo 'checked="checked"';
                                        }
                                        ?> />
                                        <?php echo TXT_UAM_NOT_LOCKED_FILE_TYPES; ?>
                                        <input name="config_parameters[<?php echo $aConfigParameters['not_locked_file_types']->getId(); ?>]"
                                               value="<?php echo $aConfigParameters['not_locked_file_types']->getValue(); ?>"/>
                                    </label>
                                    <br/>
                                    <?php echo TXT_UAM_LOCK_FILE_TYPES_DESC; ?>
                                    <?php
                                }
                                ?>
                            </td>
                            <?php
                        } elseif ($oGroupParameter->getId() === 'redirect') {
                            ?>
                            <th><?php echo TXT_UAM_REDIRECT; ?></th>
                            <td>
                                <label for="uam_redirect_no">
                                    <input type="radio" id="uam_redirect_no"
                                           name="config_parameters[<?php echo $oGroupParameter->getId(); ?>]"
                                           value="false" <?php
                                    if ($oGroupParameter->getValue() === 'false') {
                                        echo 'checked="checked"';
                                    }
                                    ?> />
                                    <?php echo TXT_UAM_NO; ?>
                                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                                <label for="uam_redirect_blog">
                                    <input type="radio" id="uam_redirect_blog"
                                           name="config_parameters[<?php echo $oGroupParameter->getId(); ?>]"
                                           value="blog" <?php
                                    if ($oGroupParameter->getValue() === 'blog') {
                                        echo 'checked="checked"';
                                    }
                                    ?> />
                                    <?php echo TXT_UAM_REDIRECT_TO_BLOG; ?>
                                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                                <label for="uam_redirect_custom_page">
                                    <input type="radio" id="uam_redirect_custom_page"
                                           name="config_parameters[<?php echo $oGroupParameter->getId(); ?>]"
                                           value="custom_page" <?php
                                    if ($oGroupParameter->getValue() === 'custom_page') {
                                        echo 'checked="checked"';
                                    }
                                    ?> />
                                    <?php echo TXT_UAM_REDIRECT_TO_PAGE; ?>
                                    <select name="config_parameters[<?php echo $aConfigParameters['redirect_custom_page']->getId(); ?>]">
                                        <?php
                                        $aPages = $this->getPages();

                                        foreach ($aPages as $oPage) {
                                            $sOption = "<option value=\"{$oPage->ID}\"";

                                            if ((int)$aConfigParameters['redirect_custom_page']->getValue() === $oPage->ID) {
                                                $sOption .= ' selected="selected"';
                                            }

                                            $sOption .= ">{$oPage->post_title}</option>";
                                            echo $sOption;
                                        }
                                        ?>
                                    </select>
                                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                                <label for="uam_redirect_custom_url">
                                    <input type="radio" id="uam_redirect_custom_url"
                                           name="config_parameters[<?php echo $oGroupParameter->getId(); ?>]"
                                           value="custom_url" <?php
                                    if ($oGroupParameter->getValue() === 'custom_url') {
                                        echo 'checked="checked"';
                                    }
                                    ?> />
                                    <?php echo TXT_UAM_REDIRECT_TO_URL; ?>
                                    <input name="config_parameters[<?php echo $aConfigParameters['redirect_custom_url']->getId(); ?>]"
                                           value="<?php echo $aConfigParameters['redirect_custom_url']->getValue(); ?>"/>
                                </label>
                                <br/>
                                <?php echo TXT_UAM_REDIRECT_DESC; ?>
                            </td>
                            <?php
                        } elseif ($oGroupParameter->getId() === 'full_access_role'
                            && $oGroupParameter instanceof \UserAccessManager\Config\SelectionConfigParameter
                        ) {
                            ?>
                            <th scope="row">
                                <label for="uam_<?php echo $oGroupParameter->getId(); ?>">
                                    <?php echo constant('TXT_UAM_'.strtoupper($oGroupParameter->getId())); ?>
                                </label>
                            </th>
                            <td>
                                <select id="uam_<?php echo $oGroupParameter->getId(); ?>"
                                        name="config_parameters[<?php echo $oGroupParameter->getId(); ?>]">
                                    <?php
                                    $aSelections = $oGroupParameter->getSelections();

                                    foreach ($aSelections as $sSelection) {
                                        ?>
                                        <option value="<?php echo $sSelection; ?>" <?php
                                        if ($oGroupParameter->getValue() === $sSelection) {
                                            echo 'selected="selected"';
                                        }
                                        ?> >
                                            <?php
                                            $sOptionNameKey = 'TXT_UAM_'.strtoupper($oGroupParameter->getId().'_'.$sSelection);

                                            if (defined($sOptionNameKey) === true) {
                                                echo constant($sOptionNameKey);
                                            } else {
                                                echo $sSelection;
                                            }
                                            ?>
                                        </option>
                                        <?php
                                    }
                                    ?>
                                </select>

                                <br/>
                                <p><?php echo constant('TXT_UAM_'.strtoupper($oGroupParameter->getId()).'_DESC'); ?></p>
                            </td>
                            <?php
                        } elseif ($oGroupParameter instanceof \UserAccessManager\Config\BooleanConfigParameter) {
                            ?>
                            <th scope="row"><?php echo constant('TXT_UAM_'.strtoupper($oGroupParameter->getId())); ?></th>
                            <td>
                                <label for="uam_<?php echo $oGroupParameter->getId(); ?>_yes">
                                    <input id="uam_<?php echo $oGroupParameter->getId(); ?>_yes"
                                           type="radio"
                                           name="config_parameters[<?php echo $oGroupParameter->getId(); ?>]"
                                           value="true" <?php
                                    if ($oGroupParameter->getValue() === true) {
                                        echo 'checked="checked"';
                                    }
                                    ?> />
                                    <?php echo TXT_UAM_YES; ?>
                                </label>&nbsp;&nbsp;&nbsp;
                                <label for="uam_<?php echo $oGroupParameter->getId(); ?>_no">
                                    <input id="uam_<?php echo $oGroupParameter->getId(); ?>_no"
                                           type="radio"
                                           name="config_parameters[<?php echo $oGroupParameter->getId(); ?>]"
                                           value="false" <?php
                                    if ($oGroupParameter->getValue() === false) {
                                        echo 'checked="checked"';
                                    }
                                    ?> />
                                    <?php echo TXT_UAM_NO; ?>
                                </label>
                                <br/>
                                <p><?php echo constant('TXT_UAM_'.strtoupper($oGroupParameter->getId()).'_DESC'); ?></p>
                            </td>
                            <?php
                        } elseif ($oGroupParameter instanceof \UserAccessManager\Config\StringConfigParameter) {
                            ?>
                            <th scope="row">
                                <label for="uam_<?php echo $oGroupParameter->getId(); ?>">
                                    <?php echo constant('TXT_UAM_'.strtoupper($oGroupParameter->getId())); ?>
                                </label>
                            </th>
                            <td>
                                <input id="uam_<?php echo $oGroupParameter->getId(); ?>"
                                       name="config_parameters[<?php echo $oGroupParameter->getId(); ?>]"
                                       value="<?php echo $oGroupParameter->getValue(); ?>"/>
                                <br/>
                                <p><?php echo constant('TXT_UAM_'.strtoupper($oGroupParameter->getId()).'_DESC'); ?></p>
                            </td>
                            <?php
                        } elseif ($oGroupParameter instanceof \UserAccessManager\Config\SelectionConfigParameter) {
                            ?>
                            <th scope="row"><?php echo constant('TXT_UAM_'.strtoupper($oGroupParameter->getId())); ?></th>
                            <td>
                                <?php
                                $aSelections = $oGroupParameter->getSelections();

                                foreach ($aSelections as $sSelection) {
                                    ?>
                                    <label for="uam_<?php echo $oGroupParameter->getId(); ?>_yes">
                                        <input id="uam_<?php echo $oGroupParameter->getId(); ?>_yes"
                                               type="radio"
                                               name="config_parameters[<?php echo $oGroupParameter->getId(); ?>]"
                                               value="<?php echo $sSelection; ?>" <?php
                                        if ($oGroupParameter->getValue() === $sSelection) {
                                            echo 'checked="checked"';
                                        }
                                        ?> />
                                        <?php
                                        $sOptionNameKey = 'TXT_UAM_'.strtoupper($oGroupParameter->getId().'_'.$sSelection);

                                        if (defined($sOptionNameKey) === true) {
                                            echo constant($sOptionNameKey);
                                        } else {
                                            echo $sSelection;
                                        }
                                        ?>
                                    </label>&nbsp;&nbsp;&nbsp;
                                    <?php
                                }
                                ?>
                                <br/>
                                <p><?php echo constant('TXT_UAM_'.strtoupper($oGroupParameter->getId()).'_DESC'); ?></p>
                            </td>
                            <?php
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