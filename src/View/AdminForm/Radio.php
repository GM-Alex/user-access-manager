<?php
/**
 * @var Radio $radio
 */

use UserAccessManager\Form\Element\Input;
use UserAccessManager\Form\Element\Radio;
use UserAccessManager\Form\Element\Select;
use UserAccessManager\Form\Element\Textarea;

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
        <div class="uam_radio_option">
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
            </label>
            <?php
            $subElement = $possibleValue->getSubElement();

            if ($subElement !== null) {
                if ($subElement instanceof Input) {
                    ?>
                    <input id="uam_<?php echo $subElement->getId(); ?>"
                           type="text"
                           name="config_parameters[<?php echo $subElement->getId(); ?>]"
                           value="<?php echo $subElement->getValue(); ?>"/>
                    <?php
                } elseif ($subElement instanceof Textarea) {
                    ?>
                    <textarea id="uam_<?php echo $subElement->getId(); ?>"
                              class="uam_textarea"
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
            ?>
        </div>
        <?php
    }
    ?>
    <p class="description"><?php echo $radio->getDescription(); ?></p>
</td>