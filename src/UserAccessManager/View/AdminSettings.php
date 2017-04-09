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
        <?php
        $groupedConfigParameters = $controller->getGroupedConfigParameters();

        /**
         * @var \UserAccessManager\Config\ConfigParameter[] $groupParameters
         */
        foreach ($groupedConfigParameters as $groupKey => $groupParameters) {
            $cssClass = $controller->isPostTypeGroup($groupKey) ? ' uam_settings_group_post_type' : '';

            ?>
            <h3><?php echo $controller->getSectionText($groupKey); ?></h3>
            <p><?php echo $controller->getSectionText($groupKey, true); ?></p>
            <table id="uam_settings_group_<?php echo $groupKey; ?>"
                   class="form-table<?php echo $cssClass; ?>">
                <tbody>
                <?php
                $configParameters = $controller->getConfigParameters();

                foreach ($groupParameters as $groupParameter) {
                    ?>
                    <tr valign="top">
                        <?php
                        if ($groupParameter->getId() === 'lock_file_types') {
                            $lockedFileTypes = $configParameters['locked_file_types'];

                            ?>
                            <th><?php
                                echo TXT_UAM_LOCK_FILE_TYPES; ?></th>
                            <td>
                                <label for="uam_lock_file_types_all">
                                    <input type="radio" id="uam_lock_file_types_all"
                                           name="config_parameters[<?php echo $groupParameter->getId(); ?>]"
                                           value="all"
                                            <?php
                                            if ($groupParameter->getValue() === 'all'
                                                || $controller->isNginx()
                                                    && $groupParameter->getValue() === 'not_selected'
                                            ) {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_ALL; ?>
                                </label>&nbsp;&nbsp;&nbsp;
                                <label for="uam_lock_file_types_selected">
                                    <input type="radio" id="uam_lock_file_types_selected"
                                           name="config_parameters[<?php echo $groupParameter->getId(); ?>]"
                                           value="selected"
                                            <?php
                                            if ($groupParameter->getValue() === 'selected') {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_LOCKED_FILE_TYPES; ?>
                                    <input name="config_parameters[<?php echo $lockedFileTypes->getId(); ?>]"
                                           value="<?php echo $lockedFileTypes->getValue(); ?>"/>
                                </label>
                                &nbsp;&nbsp;&nbsp;
                                <?php
                                if ($controller->isNginx() === false) {
                                    $notLockedFileTypes = $configParameters['not_locked_file_types'];

                                    ?>
                                    <label for="uam_lock_file_types_not_selected">
                                        <input type="radio" id="uam_lock_file_types_not_selected"
                                               name="config_parameters[<?php echo $groupParameter->getId(); ?>]"
                                               value="not_selected"
                                                <?php
                                                if ($groupParameter->getValue() === 'not_selected') {
                                                    echo 'checked="checked"';
                                                }
                                                ?>
                                        />
                                        <?php echo TXT_UAM_NOT_LOCKED_FILE_TYPES; ?>
                                        <input name="config_parameters[<?php echo $notLockedFileTypes->getId(); ?>]"
                                               value="<?php echo $notLockedFileTypes->getValue(); ?>"/>
                                    </label>
                                    <br/>
                                    <?php echo TXT_UAM_LOCK_FILE_TYPES_DESC; ?>
                                    <?php
                                }
                                ?>
                            </td>
                            <?php
                        } elseif ($groupParameter->getId() === 'redirect') {
                            $redirectCustomPage = $configParameters['redirect_custom_page'];
                            $redirectCustomUrl = $configParameters['redirect_custom_url'];

                            ?>
                            <th><?php echo TXT_UAM_REDIRECT; ?></th>
                            <td>
                                <label for="uam_redirect_no">
                                    <input type="radio" id="uam_redirect_no"
                                           name="config_parameters[<?php echo $groupParameter->getId(); ?>]"
                                           value="false"
                                            <?php
                                            if ($groupParameter->getValue() === 'false') {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_NO; ?>
                                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                                <label for="uam_redirect_blog">
                                    <input type="radio" id="uam_redirect_blog"
                                           name="config_parameters[<?php echo $groupParameter->getId(); ?>]"
                                           value="blog"
                                            <?php
                                            if ($groupParameter->getValue() === 'blog') {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_REDIRECT_TO_BLOG; ?>
                                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                                <label for="uam_redirect_custom_page">
                                    <input type="radio" id="uam_redirect_custom_page"
                                           name="config_parameters[<?php echo $groupParameter->getId(); ?>]"
                                           value="custom_page"
                                            <?php
                                            if ($groupParameter->getValue() === 'custom_page') {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_REDIRECT_TO_PAGE; ?>
                                    <select name="config_parameters[<?php echo $redirectCustomPage->getId(); ?>]">
                                        <?php
                                        $pages = $controller->getPages();

                                        foreach ($pages as $page) {
                                            $option = "<option value=\"{$page->ID}\"";
                                            $redirectValue = (int)$redirectCustomPage->getValue();

                                            if ($redirectValue === $page->ID) {
                                                $option .= ' selected="selected"';
                                            }

                                            $option .= ">{$page->post_title}</option>";
                                            echo $option;
                                        }
                                        ?>
                                    </select>
                                </label>&nbsp;&nbsp;&nbsp;&nbsp;
                                <label for="uam_redirect_custom_url">
                                    <input type="radio" id="uam_redirect_custom_url"
                                           name="config_parameters[<?php echo $groupParameter->getId(); ?>]"
                                           value="custom_url"
                                            <?php
                                            if ($groupParameter->getValue() === 'custom_url') {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_REDIRECT_TO_URL; ?>
                                    <input name="config_parameters[<?php echo $redirectCustomUrl->getId(); ?>]"
                                           value="<?php echo $redirectCustomUrl->getValue(); ?>"/>
                                </label>
                                <br/>
                                <?php echo TXT_UAM_REDIRECT_DESC; ?>
                            </td>
                            <?php
                        } elseif ($groupParameter->getId() === 'full_access_role'
                            && $groupParameter instanceof \UserAccessManager\Config\SelectionConfigParameter
                        ) {
                            ?>
                            <th scope="row">
                                <label for="uam_<?php echo $groupParameter->getId(); ?>">
                                    <?php echo $controller->getParameterText($groupKey, $groupParameter); ?>
                                </label>
                            </th>
                            <td>
                                <select id="uam_<?php echo $groupParameter->getId(); ?>"
                                        name="config_parameters[<?php echo $groupParameter->getId(); ?>]">
                                    <?php
                                    $selections = $groupParameter->getSelections();

                                    foreach ($selections as $selection) {
                                        ?>
                                        <option value="<?php echo $selection; ?>" <?php
                                        if ($groupParameter->getValue() === $selection) {
                                            echo 'selected="selected"';
                                        }
                                        ?> >
                                            <?php
                                            $optionNameKey = 'TXT_UAM_'
                                                .strtoupper($groupParameter->getId().'_'.$selection);

                                            if (defined($optionNameKey) === true) {
                                                echo constant($optionNameKey);
                                            } else {
                                                echo $selection;
                                            }
                                            ?>
                                        </option>
                                        <?php
                                    }
                                    ?>
                                </select>

                                <br/>
                                <p><?php echo $controller->getParameterText($groupKey, $groupParameter, true); ?></p>
                            </td>
                            <?php
                        } elseif ($groupParameter instanceof \UserAccessManager\Config\BooleanConfigParameter) {
                            $parameterText = $controller->getParameterText($groupKey, $groupParameter);

                            ?>
                            <th scope="row"><?php echo $parameterText; ?></th>
                            <td>
                                <label for="uam_<?php echo $groupParameter->getId(); ?>_yes">
                                    <input id="uam_<?php echo $groupParameter->getId(); ?>_yes"
                                           type="radio"
                                           name="config_parameters[<?php echo $groupParameter->getId(); ?>]"
                                           value="true"
                                            <?php
                                            if ($groupParameter->getValue() === true) {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_YES; ?>
                                </label>&nbsp;&nbsp;&nbsp;
                                <label for="uam_<?php echo $groupParameter->getId(); ?>_no">
                                    <input id="uam_<?php echo $groupParameter->getId(); ?>_no"
                                           type="radio"
                                           name="config_parameters[<?php echo $groupParameter->getId(); ?>]"
                                           value="false"
                                            <?php
                                            if ($groupParameter->getValue() === false) {
                                                echo 'checked="checked"';
                                            }
                                            ?>
                                    />
                                    <?php echo TXT_UAM_NO; ?>
                                </label>
                                <br/>
                                <p><?php echo $controller->getParameterText($groupKey, $groupParameter, true); ?></p>
                            </td>
                            <?php
                        } elseif ($groupParameter instanceof \UserAccessManager\Config\StringConfigParameter) {
                            ?>
                            <th scope="row">
                                <label for="uam_<?php echo $groupParameter->getId(); ?>">
                                    <?php echo $controller->getParameterText($groupKey, $groupParameter); ?>
                                </label>
                            </th>
                            <td>
                                <input id="uam_<?php echo $groupParameter->getId(); ?>"
                                       name="config_parameters[<?php echo $groupParameter->getId(); ?>]"
                                       value="<?php echo $groupParameter->getValue(); ?>"/>
                                <br/>
                                <p><?php echo $controller->getParameterText($groupKey, $groupParameter, true); ?></p>
                            </td>
                            <?php
                        } elseif ($groupParameter instanceof \UserAccessManager\Config\SelectionConfigParameter) {
                            $parameterText = $controller->getParameterText($groupKey, $groupParameter);

                            ?>
                            <th scope="row"><?php echo $parameterText; ?></th>
                            <td>
                                <?php
                                $selections = $groupParameter->getSelections();

                                foreach ($selections as $selection) {
                                    ?>
                                    <label for="uam_<?php echo $groupParameter->getId(); ?>_yes">
                                        <input id="uam_<?php echo $groupParameter->getId(); ?>_yes"
                                               type="radio"
                                               name="config_parameters[<?php echo $groupParameter->getId(); ?>]"
                                               value="<?php echo $selection; ?>"
                                                <?php
                                                if ($groupParameter->getValue() === $selection) {
                                                    echo 'checked="checked"';
                                                }
                                                ?>
                                        />
                                        <?php
                                        $optionNameKey = 'TXT_UAM_'
                                            .strtoupper($groupParameter->getId().'_'.$selection);

                                        if (defined($optionNameKey) === true) {
                                            echo constant($optionNameKey);
                                        } else {
                                            echo $selection;
                                        }
                                        ?>
                                    </label>&nbsp;&nbsp;&nbsp;
                                    <?php
                                }
                                ?>
                                <br/>
                                <p><?php echo $controller->getParameterText($groupKey, $groupParameter, true); ?></p>
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