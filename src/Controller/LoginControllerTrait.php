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
namespace UserAccessManager\Controller;

use UserAccessManager\Wrapper\Wordpress;

/**
 * Trait LoginControllerTrait
 *
 * @package UserAccessManager\Controller
 */
trait LoginControllerTrait
{
    use BaseControllerTrait;

    /**
     * @var Wordpress
     */
    protected $wordpress;

    /**
     * Returns the user login name.
     *
     * @return string
     */
    public function getUserLogin()
    {
        $userLogin = $this->getRequestParameter('log');
        return $this->wordpress->escHtml(stripslashes($userLogin));
    }

    /**
     * Returns true if the user is logged in.
     *
     * @return bool
     */
    public function isUserLoggedIn()
    {
        return $this->wordpress->isUserLoggedIn();
    }

    /**
     * Returns the user name of the current user
     *
     * @return string
     */
    public function getCurrentUserName()
    {
        return $this->wordpress->getCurrentUser()->display_name;
    }

    /**
     * Returns the login url.
     *
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->wordpress->wpLoginUrl($this->getRequestUrl());
    }

    /**
     * Returns the logout url.
     *
     * @return string
     */
    public function getLogoutUrl()
    {
        return $this->wordpress->wpLogoutUrl($this->getRequestUrl());
    }


    /**
     * Returns the registration url.
     *
     * @return string
     */
    public function getRegistrationUrl()
    {
        return $this->wordpress->wpRegistrationUrl();
    }

    /**
     * Returns the lost password url.
     *
     * @return string
     */
    public function getLostPasswordUrl()
    {
        return $this->wordpress->wpLostPasswordUrl($this->getRequestUrl());
    }
}
