<?php
/**
 * Select.php
 *
 * Select form field.
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
 * @var Select $select
 */

use UserAccessManager\Form\Select;

?>
<th scope="row">
    <label for="uam_<?php echo $select->getId(); ?>">
        <?php echo $select->getLabel(); ?>
    </label>
</th>
<td>
    <select id="uam_<?php echo $select->getId(); ?>"
            name="config_parameters[<?php echo $select->getId(); ?>]">
        <?php
        $possibleValues = $select->getPossibleValues();

        foreach ($possibleValues as $possibleValue) {
            ?>
            <option value="<?php echo $possibleValue->getValue(); ?>" <?php
            if ($select->getValue() === $possibleValue->getValue()) {
                echo 'selected="selected"';
            }
            if ($possibleValue->isDisabled() === true) {
                echo 'disabled="disabled"';
            }
            ?> >
                <?php echo $possibleValue->getLabel(); ?>
            </option>
            <?php
        }
        ?>
    </select>
    <br/>
    <p><?php echo $select->getDescription(); ?></p>
</td>