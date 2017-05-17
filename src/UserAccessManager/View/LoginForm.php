<?php
/**
 * LoginBar.php
 *
 * Shows the login bar.
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
 * @var UserAccessManager\Controller\FrontendController $controller
 */
if ($controller->showLoginForm() === true) {
    ?>
    <form action="<?php echo $controller->getLoginUrl(); ?>" method="post" class="uam_login_form">
        <label class="input_label" for="user_login"><?php echo TXT_UAM_LOGIN_FORM_USERNAME; ?>:</label>
        <input name="log" value="<?php echo $controller->getUserLogin(); ?>" class="input" id="user_login"
               type="text"/>
        <label class="input_label" for="user_pass"><?php echo TXT_UAM_LOGIN_FORM_PASSWORD; ?>:</label>
        <input name="pwd" class="input" id="user_pass" type="password"/>
        <input name="rememberme" class="checkbox" id="rememberme" value="forever" type="checkbox"/>
        <label class="checkbox_label" for="rememberme"><?php echo TXT_UAM_LOGIN_FORM_REMEMBER_ME; ?></label>
        <input class="button" type="submit" name="wp-submit" id="wp-submit"
               value="<?php echo TXT_UAM_LOGIN_FORM_LOGIN; ?> &raquo;"/>
        <input type="hidden" name="redirect_to" value="<?php echo $controller->getRequestUrl(); ?>"/>
    </form>
    <div class="uam_login_options">
        <?php
        if (get_option('users_can_register')) {
            ?>
            <a href="<?php echo $controller->getLoginUrl(['action' => 'register']); ?>"><?php
                echo TXT_UAM_LOGIN_FORM_REGISTER; ?></a>
            <?php
        }
        ?>
        &nbsp;
        <a href="<?php echo $controller->getLoginUrl(['action' => 'lostpassword']); ?>"
           title="<?php echo TXT_UAM_LOGIN_FORM_LOST_AND_FOUND_PASSWORD; ?>"><?php
            echo TXT_UAM_LOGIN_FORM_LOST_PASSWORD; ?></a>
    </div>
    <?php
} else {
    ?>
    <a class="uam_login_link" href="<?php echo $controller->getRedirectLoginUrl(); ?>">
        <?php TXT_UAM_LOGIN_FORM_LOGIN; ?>
    </a>
    <?php
}