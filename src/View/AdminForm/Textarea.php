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
 * @var Textarea $textarea
 */

use UserAccessManager\Form\Textarea;

?>
<th scope="row">
    <label for="uam_<?php echo $textarea->getId(); ?>">
        <?php echo $textarea->getLabel(); ?>
    </label>
</th>
<td>
    <textarea id="uam_<?php echo $textarea->getId(); ?>"
              style="width:100%;min-height:120px;"
              name="config_parameters[<?php echo $textarea->getId(); ?>]"><?php
                echo $textarea->getValue();
                ?></textarea>
    <br/>
    <p><?php echo $textarea->getDescription(); ?></p>
</td>