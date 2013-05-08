<?php
/**
 * loginBar.php
 * 
 * Shows the login bar.
 * 
 * PHP versions 5
 * 
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2013 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

if (!is_single() && !is_page()) {
    $sLoginUrl = get_bloginfo('wpurl').'/wp-login.php?redirect_to='.urlencode($_SERVER['REQUEST_URI']);
    $sLoginUrl = apply_filters('uam_login_url', $sLoginUrl);
    
    ?>
    <a class="uam_login_link" href="<?php echo $sLoginUrl; ?>"><?php echo __('Login', 'user-access-manager'); ?></a>
	<?php
} else {
    if (!isset($userLogin)) {
        $userLogin = '';
    }
    
    $sLoginUrl = get_bloginfo('wpurl').'/wp-login.php';
    $sLoginUrl = apply_filters('uam_login_form_url', $sLoginUrl);
   
    $sLoginForm = '<form action="'.$sLoginUrl.'" method="post" class="uam_login_form">';
    $sLoginForm .= '<label class="input_label" for="user_login">'.__('Username:', 'user-access-manager').'</label>';
    $sLoginForm .= '<input name="log" value="'.esc_html(stripslashes($userLogin), 1).'" class="input" id="user_login" type="text" />';
	$sLoginForm .= '<label class="input_label" for="user_pass">'.__('Password:', 'user-access-manager').'</label>';
    $sLoginForm .= '<input name="pwd" class="input" id="user_pass" type="password" />';
    $sLoginForm .= '<input name="rememberme" class="checkbox" id="rememberme" value="forever" type="checkbox" />';
    $sLoginForm .= '<label class="checkbox_label" for="rememberme">'.__('Remember me', 'user-access-manager').'</label>';
    $sLoginForm .= '<input class="button" type="submit" name="wp-submit" id="wp-submit" value="'.__('Login', 'user-access-manager').' &raquo;" />';
    $sLoginForm .= '<input type="hidden" name="redirect_to" value="'.$_SERVER['REQUEST_URI'].'" />';
    $sLoginForm .= '</form>';
    $sLoginForm .= '<div class="uam_login_options">';

    if (get_option('users_can_register')) {
         $sLoginForm .= '<a href="'.get_bloginfo('wpurl').'/wp-login.php?action=register">'.__('Register', 'user-access-manager').'</a>';
    }
    
    $sLoginForm .= '<a href="'.get_bloginfo('wpurl').'/wp-login.php?action=lostpassword" title="'.__('Password Lost and Found', 'user-access-manager').'">'.__('Lost your password?', 'user-access-manager').'</a>';
    $sLoginForm .= '</div>';

    echo apply_filters('uam_login_form', $sLoginForm);
}