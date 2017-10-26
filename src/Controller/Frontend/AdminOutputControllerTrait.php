<?php
/**
 * FrontendAdminOutputControllerTrait.php
 *
 * The FrontendTermController trait file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller\Frontend;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Trait FrontendAdminOutputControllerTrait
 *
 * @package UserAccessManager\Controller
 */
trait AdminOutputControllerTrait
{
    /**
     * @return Wordpress
     */
    abstract protected function getWordpress();

    /**
     * @return WordpressConfig
     */
    abstract protected function getWordpressConfig();

    /**
     * @return MainConfig
     */
    abstract protected function getMainConfig();

    /**
     * @return Util
     */
    abstract protected function getUtil();

    /**
     * @return UserHandler
     */
    abstract protected function getUserHandler();

    /**
     * @return UserGroupHandler
     */
    abstract protected function getUserGroupHandler();

    /**
     * Returns true if the hint text should be shown.
     *
     * @return bool
     */
    private function showAdminHint()
    {
        return $this->getWordpressConfig()->atAdminPanel() === false
            && $this->getMainConfig()->blogAdminHint() === true;
    }

    /**
     * Returns the admin hint.
     *
     * @param string  $objectType The object type.
     * @param integer $objectId   The object id we want to check.
     * @param string  $text       The text on which we want to append the hint.
     *
     * @return string
     */
    public function adminOutput($objectType, $objectId, $text = null)
    {
        $output = '';

        if ($this->showAdminHint() === true) {
            $hintText = $this->getMainConfig()->getBlogAdminHintText();

            if ($text !== null && $this->getUtil()->endsWith($text, $hintText) === true) {
                return $output;
            }

            if ($this->getUserHandler()->userIsAdmin($this->getWordpress()->getCurrentUser()->ID) === true
                && count($this->getUserGroupHandler()->getUserGroupsForObject($objectType, $objectId)) > 0
            ) {
                $output .= $hintText;
            }
        }

        return $output;
    }
}
