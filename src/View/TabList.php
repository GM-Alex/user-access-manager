<?php
/**
 * TabList.php
 *
 * Shows the tab list at the admin panel.
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
 * @var ControllerTabNavigationTrait $controller
 */

use UserAccessManager\Controller\Backend\ControllerTabNavigationTrait;

?>
    <h2 class="nav-tab-wrapper">
        <?php
        $currentGroupKey = $controller->getCurrentTabGroup();
        $tabGroups = $controller->getTabGroups();

        foreach ($tabGroups as $group => $defaultSection) {
            $cssClass = 'nav-tab';

            if ($currentGroupKey === $group) {
                $cssClass .= ' nav-tab-active';
            }
            ?>
            <a class="<?php echo $cssClass; ?>"
               href="<?php echo $controller->getTabGroupLink($group); ?>">
                <?php echo $controller->getGroupText($group); ?>
            </a>
            <?php
        }
        ?>
    </h2>
<?php
$sections = $controller->getSections();
if (count($sections) > 1) {
    $currentSection = $controller->getCurrentTabGroupSection();

    ?>
    <table class="form-table">
        <tbody>
        <tr>
            <th>
                <label for="uam_settings_group_section">
                    <?php echo $controller->getGroupText($currentGroupKey . '_SECTION_SELECTION'); ?>
                </label>
            </th>
            <td>
                <select id="uam_settings_group_section" name="section">
                    <?php
                    foreach ($sections as $section) {
                        ?>
                        <option value="<?php echo $section ?>"
                                data-link="<?php
                                echo $controller->getTabGroupSectionLink($currentGroupKey, $section);
                                ?>"
                            <?php
                            if ($currentSection === $section) {
                                echo 'selected="selected"';
                            }
                            ?>><?php
                            echo $controller->getGroupSectionText($section);
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
