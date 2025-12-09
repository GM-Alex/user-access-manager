<?php
/**
 * @var UserAccessManager\Controller\Frontend\LoginControllerTrait $controller
 */
if ($controller->isUserLoggedIn() === false) {
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
            <a href="<?php echo $controller->getRegistrationUrl(); ?>"><?php
                echo TXT_UAM_LOGIN_FORM_REGISTER; ?></a>
            <?php
        }
        ?>
        &nbsp;
        <a href="<?php echo $controller->getLostPasswordUrl(); ?>"
           title="<?php echo TXT_UAM_LOGIN_FORM_LOST_AND_FOUND_PASSWORD; ?>"><?php
            echo TXT_UAM_LOGIN_FORM_LOST_PASSWORD; ?></a>
    </div>
    <?php
} else {
    ?>
    <div class="uam_login_options">
        <?php echo sprintf(TXT_UAM_LOGIN_FORM_WELCOME_MESSAGE, $controller->getCurrentUserName()); ?><br>
        <a href="<?php echo $controller->getLogoutUrl(); ?>" title="<?php echo TXT_UAM_LOGIN_FORM_LOGOUT; ?>">
            <?php echo TXT_UAM_LOGIN_FORM_LOGOUT; ?>
        </a>
    </div>
    <?php
}
