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
 * @copyright 2008-2010 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

if (!is_single()) {
    ?>
    <a href="<?php echo get_bloginfo('wpurl') ?>/wp-login.php?redirect_to=<?php echo urlencode($_SERVER['REQUEST_URI'])?>">
    	<?php __('Login', 'user-access-manager'); ?>
    </a>;
	<?php
} else {
    if (!isset($userLogin)) {
        $userLogin = '';
    }
    ?>
    <form action="<?php echo get_bloginfo('wpurl') ?>/wp-login.php" method="post" >
        <p>
        	<label for="user_login">
        	    <?php echo __('Username:', 'user-access-manager') ?>
        	    <input name="log" value="<?php echo wp_specialchars(stripslashes($userLogin), 1) ?>" class="input" id="user_login" type="text" />
        	</label>
       	</p>
        <p>
        	<label for="user_pass">
        	    <?php echo __('Password:', 'user-access-manager') ?>
        	    <input name="pwd" class="imput" id="user_pass" type="password" />
        	</label>
       	</p>
        <p class="forgetmenot">
        	<label for="rememberme">
        		<input name="rememberme" class="checkbox" id="rememberme" value="forever" type="checkbox" />
        		<?php echo __('Remember me', 'user-access-manager') ?>
        	</label>
        </p>
        <p class="submit">
        	<input type="submit" name="wp-submit" id="wp-submit" value="<?php __('Login', 'user-access-manager') ?> &raquo;" />';
       	</p>
        <input type="hidden" name="redirect_to" value="<?php echo $_SERVER['REQUEST_URI'] ?>" />';
    </form>';
    <p>
    <?php 
    if (get_option('users_can_register')) {
        ?>
        <a href="<?php echo get_bloginfo('wpurl') ?>/wp-login.php?action=register">
            <?php echo __('Register', 'user-access-manager') ?>
       	</a><br/>
        <?php
    }
    ?>
    	<a href="<?php echo get_bloginfo('wpurl') ?>/wp-login.php?action=lostpassword" title="<?php __('Password Lost and Found', 'user-access-manager') ?>">
    	    <?php __('Lost your password?', 'user-access-manager') ?>
    	</a>';
    </p>
    <?php
}