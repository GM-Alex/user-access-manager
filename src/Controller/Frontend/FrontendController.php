<?php

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

    public function enqueueStylesAndScripts(): void
    {
        $this->wordpress->registerStyle(
            self::HANDLE_STYLE_LOGIN_FORM,
            $this->wordpressConfig->getUrlPath().'assets/css/uamLoginForm.css',
            [],
            UserAccessManager::VERSION,
            'screen'
        );
        $this->wordpress->enqueueStyle(self::HANDLE_STYLE_LOGIN_FORM);
    }

    /**
     * @throws UserGroupTypeException
     */
    public function showAncestors(array $ancestors, int|string|null $objectId, string $objectType): array
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

    /**
     * @throws UserGroupTypeException
     */
    public function getWpSeoUrl(array|string $url, string $type, object $object): bool|array|string
    {
        return ($this->accessHandler->checkObjectAccess($type, $object->ID) === true) ? $url : false;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function getElementorContent(mixed $content): mixed
    {
        $this->wordpress->removeAction('elementor/frontend/the_content', [$this, 'getElementorContent']);
        $post = $this->wordpress->getCurrentPost();

        if ($this->accessHandler->checkObjectAccess($post->post_type, $post->ID) === false) {
            $content = htmlspecialchars_decode($this->mainConfig->getPostTypeContent($post->post_type));
        }

        return $content;
    }
}
