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

declare(strict_types=1);

namespace UserAccessManager\Controller\Frontend;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
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
    abstract protected function getWordpress(): Wordpress;

    /**
     * @return WordpressConfig
     */
    abstract protected function getWordpressConfig(): WordpressConfig;

    /**
     * @return MainConfig
     */
    abstract protected function getMainConfig(): MainConfig;

    /**
     * @return Util
     */
    abstract protected function getUtil(): Util;

    /**
     * @return UserHandler
     */
    abstract protected function getUserHandler(): UserHandler;

    /**
     * @return UserGroupHandler
     */
    abstract protected function getUserGroupHandler(): UserGroupHandler;

    /**
     * Returns true if the hint text should be shown.
     * @return bool
     */
    private function showAdminHint(): bool
    {
        return $this->getWordpressConfig()->atAdminPanel() === false
            && $this->getMainConfig()->blogAdminHint() === true;
    }

    /**
     * Returns the admin hint.
     * @param string $objectType The object type.
     * @param int|string $objectId The object id we want to check.
     * @param null $text The text on which we want to append the hint.
     * @return string
     * @throws UserGroupTypeException
     */
    public function adminOutput(string $objectType, $objectId, $text = null): string
    {
        if ($this->showAdminHint() === true) {
            $hintText = $this->getMainConfig()->getBlogAdminHintText();

            if ($text !== null && $this->getUtil()->endsWith($text, $hintText) === true) {
                return '';
            }

            if ($this->getUserHandler()->userIsAdmin($this->getWordpress()->getCurrentUser()->ID) === true
                && count($this->getUserGroupHandler()->getUserGroupsForObject($objectType, $objectId)) > 0
            ) {
                return $hintText;
            }
        }

        return '';
    }
}
