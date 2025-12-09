<?php
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