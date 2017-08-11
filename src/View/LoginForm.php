<?php
/**
 * LoginForm.php
 *
 * Shows the login form.
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
 * @var UserAccessManager\Controller\Frontend\LoginControllerTrait $controller
 */
if ($controller->showLoginForm() === true) {
    include 'Login/LoginForm.php';
} else {
    ?>
    <a class="uam_login_link" href="<?php echo $controller->getRedirectLoginUrl(); ?>">
        <?php TXT_UAM_LOGIN_FORM_LOGIN; ?>
    </a>
    <?php
}