<?php
/**
 * AdminAbout.php
 *
 * Shows the about page at the admin panel.
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
 * @var AboutController $controller
 */

use UserAccessManager\Controller\Backend\AboutController;

?>
<div class="wrap">
    <h2><?php echo TXT_UAM_ABOUT; ?></h2>
    <div id="poststuff">
        <div class="postbox">
            <h3 class="hndle"><?php echo TXT_UAM_HOW_TO_SUPPORT; ?></h3>
            <div class="inside">
                <h3><?php echo TXT_UAM_SEND_REPORTS_HEAD; ?></h3>
                <p><?php echo TXT_UAM_SEND_REPORTS; ?></p>
                <h3><?php echo TXT_UAM_CREATE_TRANSLATION_HEAD; ?></h3>
                <p><?php echo TXT_UAM_CREATE_TRANSLATION; ?></p>
                <h3><?php echo TXT_UAM_SUPPORT_ME_ON_STEADY_HEAD; ?></h3>
                <p><?php include 'SteadyBanner.php'; ?></p>
                <h3><?php echo TXT_UAM_DONATE_HEAD; ?></h3>
                <p>
                    <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=1947052">
                        <img style="margin:4px 0;" alt="Make payments with PayPal - it's fast, free and secure!"
                             name="submit" src="https://www.paypal.com/en_US/i/btn/x-click-but21.gif"/>
                    </a>
                </p>
                <h3><?php echo TXT_UAM_SPREAD_THE_WORD_HEAD; ?></h3>
                <p><?php echo TXT_UAM_SPREAD_THE_WORD; ?></p>
            </div>
        </div>
        <div class="postbox">
            <h3 class="hndle"><?php echo TXT_UAM_TOP_SUPPORTERS; ?></h3>
            <div class="inside">
                <?php
                $topSupporters = $controller->getTopSupporters();

                if ($topSupporters === []) {
                    ?>
                    <h3><?php echo TXT_UAM_STEADY_BE_THE_FIRST; ?></h3>
                    <p><?php include 'SteadyBanner.php'; ?></p>
                    <?php
                } else {
                    ?>
                    <ul class="uam_supporters">
                        <?php
                        foreach ($topSupporters as $topSupporter) {
                            ?>
                            <li><?php echo $topSupporter; ?></li>
                            <?php
                        }
                        ?>
                    </ul>
                    <?php
                }
                ?>
            </div>
        </div>
        <div class="postbox">
            <h3 class="hndle"><?php echo TXT_UAM_SUPPORTERS; ?></h3>
            <div class="inside">
                <?php
                $supporters = $controller->getSupporters();

                if ($supporters === []) {
                    ?>
                    <h3><?php echo TXT_UAM_STEADY_BE_THE_FIRST; ?></h3>
                    <p><?php include 'SteadyBanner.php'; ?></p>
                    <?php
                } else {
                    ?>
                    <ul class="uam_supporters">
                        <?php
                        foreach ($supporters as $supporter) {
                            ?>
                            <li><?php echo $supporter; ?></li>
                            <?php
                        }
                        ?>
                    </ul>
                    <?php
                }
                ?>
            </div>
        </div>
        <div class="postbox">
            <h3 class="hndle"><?php echo TXT_UAM_SPECIAL_THANKS; ?></h3>
            <div class="inside">
                <ul class="uam_supporters">
                    <li><?php echo TXT_UAM_SPECIAL_THANKS_FIRST; ?></li>
                    <?php
                    foreach ($controller->getSpecialThanks() as $specialThank) {
                        ?>
                        <li><?php echo $specialThank; ?></li>
                        <?php
                    }
                    ?>
                    <li><?php echo TXT_UAM_SPECIAL_THANKS_LAST; ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>