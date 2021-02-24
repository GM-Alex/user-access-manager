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
    const HANDLE_STYLE_LOGIN_FORM = 'UserAccessManagerLoginForm';

    /**
     * @var MainConfig
     */
    private $mainConfig;

    /**
     * @var AccessHandler
     */
    private $accessHandler;

    /**
     * FrontendController constructor.
      * @param Php             $php
     * @param Wordpress       $wordpress
     * @param WordpressConfig $wordpressConfig
     * @param MainConfig      $mainConfig
     * @param AccessHandler   $userHandler
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        AccessHandler $userHandler
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
        $this->mainConfig = $mainConfig;
        $this->accessHandler = $userHandler;
    }

    /**
     * Functions for other content.
     */

    /**
     * Register all other styles.
     */
    private function registerStylesAndScripts()
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

    /**
     * The function for the wp_enqueue_scripts action.
     */
    public function enqueueStylesAndScripts()
    {
        $this->registerStylesAndScripts();
        $this->wordpress->enqueueStyle(self::HANDLE_STYLE_LOGIN_FORM);
    }

    /**
     * The function for the get_ancestors filter.
      * @param array $ancestors
     * @param int|string $objectId
     * @param string $objectType
      * @return array
     * @throws UserGroupTypeException
     */
    public function showAncestors(array $ancestors, $objectId, string $objectType): array
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
     * Filter for Yoast SEO Plugin
     * Hides the url from the site map if the user has no access
     * @param string|array $url The url to check
     * @param string $type The object type
     * @param object $object The object
     * @return false|string
     * @throws UserGroupTypeException
     */
    public function getWpSeoUrl($url, string $type, object $object)
    {
        return ($this->accessHandler->checkObjectAccess($type, $object->ID) === true) ? $url : false;
    }
}
