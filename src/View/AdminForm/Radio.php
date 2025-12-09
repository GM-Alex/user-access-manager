<?php
/**
 * @var Radio $radio
 */

use UserAccessManager\Form\Input;
use UserAccessManager\Form\Radio;
use UserAccessManager\Form\Select;
use UserAccessManager\Form\Textarea;

?>
<th scope="row"><?php echo $radio->getLabel(); ?></th>
<td>
    <?php
    $possibleValues = $radio->getPossibleValues();

    foreach ($possibleValues as $possibleValue) {
        $rawValue = $possibleValue->getValue();
        $formValue = (is_bool($rawValue) === true) ?
            (($rawValue === true) ? 'true' : 'false') : $rawValue;

        ?>
        <label for="uam_<?php echo $radio->getId() . '_' . $formValue; ?>">
            <input id="uam_<?php echo $radio->getId() . '_' . $formValue; ?>"
                   type="radio"
                   name="config_parameters[<?php echo $radio->getId(); ?>]"
                   value="<?php echo $formValue; ?>"
                <?php
                if ($radio->getValue() === $possibleValue->getValue()) {
                    echo 'checked="checked"';
                }
                ?>
            />
            <?php echo $possibleValue->getLabel(); ?>
        </label>&nbsp;&nbsp;&nbsp;
        <?php
        $subElement = $possibleValue->getSubElement();

        if ($subElement !== null) {
            if ($subElement instanceof Input) {
                ?>
                <input id="uam_<?php echo $subElement->getId(); ?>"
                       name="config_parameters[<?php echo $subElement->getId(); ?>]"
                       value="<?php echo $subElement->getValue(); ?>"/>
                <?php
            } elseif ($subElement instanceof Textarea) {
                ?>
                <textarea id="uam_<?php echo $subElement->getId(); ?>"
                          style="width:100%;min-height:120px;"
                          name="config_parameters[<?php echo $subElement->getId(); ?>]"><?php
                            echo htmlentities($subElement->getValue());
                            ?></textarea>
                <?php
            } elseif ($subElement instanceof Select) {
                ?>
                <select id="uam_<?php echo $subElement->getId(); ?>"
                        name="config_parameters[<?php echo $subElement->getId(); ?>]">
                    <?php
                    $subPossibleValues = $subElement->getPossibleValues();

                    foreach ($subPossibleValues as $subPossibleValue) {
                        ?>
                        <option value="<?php echo $subPossibleValue->getValue(); ?>" <?php
                        if ($subElement->getValue() === $subPossibleValue->getValue()) {
                            echo 'selected="selected"';
                        }
                        ?> >
                            <?php echo $subPossibleValue->getLabel(); ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
                <?php
            }
        }
    }
    ?>
    <br/>
    <p><?php echo $radio->getDescription(); ?></p>
</td>