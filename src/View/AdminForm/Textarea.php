<?php
/**
 * @var Textarea $textarea
 */

use UserAccessManager\Form\Element\Textarea;

?>
<th scope="row">
    <label for="uam_<?php echo $textarea->getId(); ?>">
        <?php echo $textarea->getLabel(); ?>
    </label>
</th>
<td>
    <textarea id="uam_<?php echo $textarea->getId(); ?>"
              class="uam_textarea"
              name="config_parameters[<?php echo $textarea->getId(); ?>]"><?php
                echo $textarea->getValue();
                ?></textarea>
    <br/>
    <p class="description"><?php echo $textarea->getDescription(); ?></p>
</td>