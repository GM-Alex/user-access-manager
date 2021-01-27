<?php
/**
 * Input.php
 *
 * Input form field.
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
 * @var Input $input
 */

use UserAccessManager\Form\Input;

?>
<th scope="row">
    <label for="uam_<?php echo $input->getId(); ?>">
        <?php echo $input->getLabel(); ?>
    </label>
</th>
<td>
    <input id="uam_<?php echo $input->getId(); ?>"
           name="config_parameters[<?php echo $input->getId(); ?>]"
           value="<?php echo $input->getValue(); ?>"/>
    <br/>
    <p><?php echo $input->getDescription(); ?></p>
</td>