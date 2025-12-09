<?php
/**
 * FrontendController.php
 *
 * The FrontendController class file.
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

use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\UserAccessManager;
use UserAccessManager\UserGroup\UserGroupTypeException;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class FrontendController
 *
 * @package UserAccessManager\Controller
 */
class FrontendController extends Controller
{
    public const HANDLE_STYLE_LOGIN_FORM = 'UserAccessManagerLoginForm';

    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        private MainConfig $mainConfig,
        private AccessHandler $accessHandler
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
    }

    private function registerStylesAndScripts(): void
    {
        $urlPath = $this->wordpressConfig->getUrlPath();

        $this->wordpress->registerStyle(
            self::HANDLE_STYLE_LOGIN_FORM,
            $urlPath.'assets/css/uamLoginForm.css',
            [],
            UserAccessManager::VERSION,
            'screen'
        );
    }

    public function enqueueStylesAndScripts(): void
    {
        $this->registerStylesAndScripts();
        $this->wordpress->enqueueStyle(self::HANDLE_STYLE_LOGIN_FORM);
    }

    /**
     * @throws UserGroupTypeException
     */
    public function showAncestors(array $ancestors, int|string $objectId, string $objectType): array
    {
        if ($this->mainConfig->lockRecursive() === true
            && $this->accessHandler->checkObjectAccess($objectType, $objectId) === false
        ) {
            return [];
        }

        foreach ($ancestors as $key => $ancestorId) {
            if ($this->accessHandler->checkObjectAccess($objectType, $ancestorId) === false) {
                unset($ancestors[$key]);
            }
        }

        return $ancestors;
    }


    /*
     * Functions for the redirection and files.
     */

    /**
     * @throws UserGroupTypeException
     */
    public function getWpSeoUrl(array|string $url, string $type, object $object): bool|array|string
    {
        return ($this->accessHandler->checkObjectAccess($type, $object->ID) === true) ? $url : false;
    }

    /*
     * Elementor
     */

    /**
     * @throws UserGroupTypeException
     */
    public function getElementorContent($content): mixed
    {
        $this->wordpress->removeAction('elementor/frontend/the_content', [$this, 'getElementorContent']);
        $post = $this->wordpress->getCurrentPost();

        if ($this->accessHandler->checkObjectAccess($post->post_type, $post->ID) === false) {
            $content = htmlspecialchars_decode($this->mainConfig->getPostTypeContent($post->post_type));
        }

        return $content;
    }
}
