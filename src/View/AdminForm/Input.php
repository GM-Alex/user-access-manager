<?php
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