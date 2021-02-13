<?php
/**
 * LoginControllerTrait.php
 *
 * The LoginControllerTrait trait file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

declare(strict_types=1);

namespace UserAccessManager\Controller\Frontend;

use UserAccessManager\Wrapper\Wordpress;

/**
 * Trait LoginControllerTrait
 *
 * @package UserAccessManager\Controller
 */
trait LoginControllerTrait
{
    /**
     * @return Wordpress
     */
    abstract protected function getWordpress(): Wordpress;

    /**
     * @return string
     */
    abstract public function getRequestUrl(): string;

    /**
     * @param string $name
     * @param mixed $default
      * @return mixed
     */
    abstract public function getRequestParameter(string $name, $default = null);

    /**
     * Returns the user login name.
      * @return string
     */
    public function getUserLogin(): string
    {
        $userLogin = $this->getRequestParameter('log');
        return $this->getWordpress()->escHtml(stripslashes((string) $userLogin));
    }

    /**
     * Returns true if the user is logged in.
      * @return bool
     */
    public function isUserLoggedIn(): bool
    {
        return $this->getWordpress()->isUserLoggedIn();
    }

    /**
     * Returns the user name of the current user
      * @return string
     */
    public function getCurrentUserName(): string
    {
        return $this->getWordpress()->getCurrentUser()->display_name;
    }

    /**
     * Returns the login url.
      * @return string
     */
    public function getLoginUrl(): string
    {
        return $this->getWordpress()->wpLoginUrl($this->getRequestUrl());
    }

    /**
     * Returns the logout url.
      * @return string
     */
    public function getLogoutUrl(): string
    {
        return $this->getWordpress()->wpLogoutUrl($this->getRequestUrl());
    }


    /**
     * Returns the registration url.
      * @return string
     */
    public function getRegistrationUrl(): string
    {
        return $this->getWordpress()->wpRegistrationUrl();
    }

    /**
     * Returns the lost password url.
      * @return string
     */
    public function getLostPasswordUrl(): string
    {
        return $this->getWordpress()->wpLostPasswordUrl($this->getRequestUrl());
    }

    /**
     * Checks if we allowed show the login form.
      * @return bool
     */
    public function showLoginForm(): bool
    {
        return $this->getWordpress()->isSingle() === true || $this->getWordpress()->isPage() === true;
    }

    /**
     * Returns the login redirect url.
      * @return mixed
     */
    public function getRedirectLoginUrl()
    {
        $loginUrl = $this->getWordpress()->getBlogInfo('wpurl')
            .'/wp-login.php?redirect_to='.urlencode($_SERVER['REQUEST_URI']);
        return $this->getWordpress()->applyFilters('uam_login_url', $loginUrl);
    }
}
