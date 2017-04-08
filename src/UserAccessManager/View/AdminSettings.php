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
 * @var \UserAccessManager\Controller\AdminSettingsController $Controller
 */
if ($Controller->hasUpdateMessage()) {
    ?>
    <div class="updated">
        <p><strong><?php echo $Controller->getUpdateMessage(); ?></strong></p>
    </div>
    <?php
}

?>
<div class="wrap">
    <form method="post" action="<?php echo $Controller->getRequestUrl(); ?>">
        <?php $Controller->createNonceField('uamUpdateSettings'); ?>
        <input type="hidden" name="uam_action" value="update_settings"/>
        <h2><?php echo TXT_UAM_SETTINGS; ?></h2>
        <?php
        $aGroupedConfigParameters = $Controller->getGroupedConfigParameters();

        /**
         * @var \UserAccessManager\Config\ConfigParameter[] $aGroupParameters
         */
        foreach ($aGroupedConfigParameters as $sGroupKey => $aGroupParameters) {
            $sCssClass = $Controller->isPostTypeGroup($sGroupKey) ? ' uam_settings_group_post_type' : '';

            ?>
            <h3><?php echo $Controller->getSectionText($sGroupKey); ?></h3>
            <p><?php echo $Controller->getSectionText($sGroupKey, true); ?></p>
            <table id="uam_settings_group_<?php echo $sGroupKey; ?>"
                   class="form-table<?php echo $sCssClass; ?>">
                <tbody>
                <?php
                $aConfigParameters = $Controller->getConfigParameters();

                foreach ($aGroupParameters as $GroupParameter) {
                    ?>
                    <tr valign="top">
                        <?php
                        if ($GroupParameter->getId() === 'lock_file_types') {
                            $LockedFileTypes = $aConfigParameters['locked_file_types'];

                            ?>
                            <th><?php
                                echo TXT_UAM_LOCK_FILE_TYPES; ?></th>
                            <td>
                                <label for="uam_lock_file_types_all">
                                    <input type="radio" id="uam_lock_file_types_all"
                                           name="config_parameters[<?php echo $GroupParameter->getId(); ?>]"
                                           value="all"
                                            <?php
                                            if ($GroupParameter->getValue() === 'all'
                                                || $Controller->isNginx()
                                                    && $GroupParameter->getValue() === 'not_selected'
                                            ) {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_ALL; ?>
                                </label>&nbsp;&nbsp;&nbsp;
                                <label for="uam_lock_file_types_selected">
                                    <input type="radio" id="uam_lock_file_types_selected"
                                           name="config_parameters[<?php echo $GroupParameter->getId(); ?>]"
                                           value="selected"
                                            <?php
                                            if ($GroupParameter->getValue() === 'selected') {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_LOCKED_FILE_TYPES; ?>
                                    <input name="config_parameters[<?php echo $LockedFileTypes->getId(); ?>]"
                                           value="<?php echo $LockedFileTypes->getValue(); ?>"/>
                                </label>
                                &nbsp;&nbsp;&nbsp;
                                <?php
                                if ($Controller->isNginx() === false) {
                                    $NotLockedFileTypes = $aConfigParameters['not_locked_file_types'];

                                    ?>
                                    <label for="uam_lock_file_types_not_selected">
                                        <input type="radio" id="uam_lock_file_types_not_selected"
                                               name="config_parameters[<?php echo $GroupParameter->getId(); ?>]"
                                               value="not_selected"
                                                <?php
                                                if ($GroupParameter->getValue() === 'not_selected') {
                                                    echo 'checked="checked"';
                                                }
                                                ?>
                                        />
                                        <?php echo TXT_UAM_NOT_LOCKED_FILE_TYPES; ?>
                                        <input name="config_parameters[<?php echo $NotLockedFileTypes->getId(); ?>]"
                                               value="<?php echo $NotLockedFileTypes->getValue(); ?>"/>
                                    </label>
                                    <br/>
                                    <?php echo TXT_UAM_LOCK_FILE_TYPES_DESC; ?>
                                    <?php
                                }
                                ?>
                            </td>
                            <?php
                        } elseif ($GroupParameter->getId() === 'redirect') {
                            $RedirectCustomPage = $aConfigParameters['redirect_custom_page'];
                            $RedirectCustomUrl = $aConfigParameters['redirect_custom_url'];

                            ?>
                            <th><?php echo TXT_UAM_REDIRECT; ?></th>
                            <td>
                                <label for="uam_redirect_no">
                                    <input type="radio" id="uam_redirect_no"
                                           name="config_parameters[<?php echo $GroupParameter->getId(); ?>]"
                                           value="false"
                                            <?php
                                            if ($GroupParameter->getValue() === 'false') {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_NO; ?>
                                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                                <label for="uam_redirect_blog">
                                    <input type="radio" id="uam_redirect_blog"
                                           name="config_parameters[<?php echo $GroupParameter->getId(); ?>]"
                                           value="blog"
                                            <?php
                                            if ($GroupParameter->getValue() === 'blog') {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_REDIRECT_TO_BLOG; ?>
                                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                                <label for="uam_redirect_custom_page">
                                    <input type="radio" id="uam_redirect_custom_page"
                                           name="config_parameters[<?php echo $GroupParameter->getId(); ?>]"
                                           value="custom_page"
                                            <?php
                                            if ($GroupParameter->getValue() === 'custom_page') {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_REDIRECT_TO_PAGE; ?>
                                    <select name="config_parameters[<?php echo $RedirectCustomPage->getId(); ?>]">
                                        <?php
                                        $aPages = $Controller->getPages();

                                        foreach ($aPages as $Page) {
                                            $sOption = "<option value=\"{$Page->ID}\"";
                                            $iRedirectValue = (int)$RedirectCustomPage->getValue();

                                            if ($iRedirectValue === $Page->ID) {
                                                $sOption .= ' selected="selected"';
                                            }

                                            $sOption .= ">{$Page->post_title}</option>";
                                            echo $sOption;
                                        }
                                        ?>
                                    </select>
                                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                                <label for="uam_redirect_custom_url">
                                    <input type="radio" id="uam_redirect_custom_url"
                                           name="config_parameters[<?php echo $GroupParameter->getId(); ?>]"
                                           value="custom_url"
                                            <?php
                                            if ($GroupParameter->getValue() === 'custom_url') {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_REDIRECT_TO_URL; ?>
                                    <input name="config_parameters[<?php echo $RedirectCustomUrl->getId(); ?>]"
                                           value="<?php echo $RedirectCustomUrl->getValue(); ?>"/>
                                </label>
                                <br/>
                                <?php echo TXT_UAM_REDIRECT_DESC; ?>
                            </td>
                            <?php
                        } elseif ($GroupParameter->getId() === 'full_access_role'
                            && $GroupParameter instanceof \UserAccessManager\Config\SelectionConfigParameter
                        ) {
                            ?>
                            <th scope="row">
                                <label for="uam_<?php echo $GroupParameter->getId(); ?>">
                                    <?php echo $Controller->getParameterText($sGroupKey, $GroupParameter); ?>
                                </label>
                            </th>
                            <td>
                                <select id="uam_<?php echo $GroupParameter->getId(); ?>"
                                        name="config_parameters[<?php echo $GroupParameter->getId(); ?>]">
                                    <?php
                                    $aSelections = $GroupParameter->getSelections();

                                    foreach ($aSelections as $sSelection) {
                                        ?>
                                        <option value="<?php echo $sSelection; ?>" <?php
                                        if ($GroupParameter->getValue() === $sSelection) {
                                            echo 'selected="selected"';
                                        }
                                        ?> >
                                            <?php
                                            $sOptionNameKey = 'TXT_UAM_'
                                                .strtoupper($GroupParameter->getId().'_'.$sSelection);

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
                                <p><?php echo $Controller->getParameterText($sGroupKey, $GroupParameter, true); ?></p>
                            </td>
                            <?php
                        } elseif ($GroupParameter instanceof \UserAccessManager\Config\BooleanConfigParameter) {
                            $sParameterText = $Controller->getParameterText($sGroupKey, $GroupParameter);

                            ?>
                            <th scope="row"><?php echo $sParameterText; ?></th>
                            <td>
                                <label for="uam_<?php echo $GroupParameter->getId(); ?>_yes">
                                    <input id="uam_<?php echo $GroupParameter->getId(); ?>_yes"
                                           type="radio"
                                           name="config_parameters[<?php echo $GroupParameter->getId(); ?>]"
                                           value="true"
                                            <?php
                                            if ($GroupParameter->getValue() === true) {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_YES; ?>
                                </label>&nbsp;&nbsp;&nbsp;
                                <label for="uam_<?php echo $GroupParameter->getId(); ?>_no">
                                    <input id="uam_<?php echo $GroupParameter->getId(); ?>_no"
                                           type="radio"
                                           name="config_parameters[<?php echo $GroupParameter->getId(); ?>]"
                                           value="false"
                                            <?php
                                            if ($GroupParameter->getValue() === false) {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_NO; ?>
                                </label>
                                <br/>
                                <p><?php echo $Controller->getParameterText($sGroupKey, $GroupParameter, true); ?></p>
                            </td>
                            <?php
                        } elseif ($GroupParameter instanceof \UserAccessManager\Config\StringConfigParameter) {
                            ?>
                            <th scope="row">
                                <label for="uam_<?php echo $GroupParameter->getId(); ?>">
                                    <?php echo $Controller->getParameterText($sGroupKey, $GroupParameter); ?>
                                </label>
                            </th>
                            <td>
                                <input id="uam_<?php echo $GroupParameter->getId(); ?>"
                                       name="config_parameters[<?php echo $GroupParameter->getId(); ?>]"
                                       value="<?php echo $GroupParameter->getValue(); ?>"/>
                                <br/>
                                <p><?php echo $Controller->getParameterText($sGroupKey, $GroupParameter, true); ?></p>
                            </td>
                            <?php
                        } elseif ($GroupParameter instanceof \UserAccessManager\Config\SelectionConfigParameter) {
                            $sParameterText = $Controller->getParameterText($sGroupKey, $GroupParameter);

                            ?>
                            <th scope="row"><?php echo $sParameterText; ?></th>
                            <td>
                                <?php
                                $aSelections = $GroupParameter->getSelections();

                                foreach ($aSelections as $sSelection) {
                                    ?>
                                    <label for="uam_<?php echo $GroupParameter->getId(); ?>_yes">
                                        <input id="uam_<?php echo $GroupParameter->getId(); ?>_yes"
                                               type="radio"
                                               name="config_parameters[<?php echo $GroupParameter->getId(); ?>]"
                                               value="<?php echo $sSelection; ?>"
                                                <?php
                                                if ($GroupParameter->getValue() === $sSelection) {
                                                    echo 'checked="checked"';
                                                }
                                                ?>
                                        />
                                        <?php
                                        $sOptionNameKey = 'TXT_UAM_'
                                            .strtoupper($GroupParameter->getId().'_'.$sSelection);

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
                                <p><?php echo $Controller->getParameterText($sGroupKey, $GroupParameter, true); ?></p>
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