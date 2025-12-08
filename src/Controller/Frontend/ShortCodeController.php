<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Frontend;

use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;


class ShortCodeController extends Controller
{
    use LoginControllerTrait;

    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        protected UserGroupHandler $userGroupHandler
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
    }

    protected function getWordpress(): Wordpress
    {
        return $this->wordpress;
    }

    public function getLoginFormHtml(): string
    {
        $loginForm = '';

        if ($this->wordpress->isUserLoggedIn() === false) {
            $loginForm = $this->getIncludeContents('LoginForm.php');
        }

        return $this->wordpress->applyFilters('uam_login_form', $loginForm);
    }

    public function loginFormShortCode(): string
    {
        return $this->getLoginFormHtml();
    }

    public function publicShortCode(mixed $attributes, string $content = ''): string
    {
        return ($this->wordpress->isUserLoggedIn() === false) ? $this->wordpress->doShortCode($content) : '';
    }

    private function getUserGroupsMapFromAttributes(array $attributes): array
    {
        $userGroups = (isset($attributes['group']) === true) ? explode(',', $attributes['group']) : [];
        return array_flip(array_map('trim', $userGroups));
    }

    /**
     * @throws UserGroupTypeException
     */
    public function privateShortCode(array $attributes, string $content = ''): string
    {
        if ($this->wordpress->isUserLoggedIn() === true) {
            $userGroupMap = $this->getUserGroupsMapFromAttributes($attributes);

            if ($userGroupMap === []) {
                return $this->wordpress->doShortCode($content);
            }

            $userUserGroups = $this->userGroupHandler->getUserGroupsForUser();

            foreach ($userUserGroups as $userGroup) {
                if (isset($userGroupMap[$userGroup->getId()])
                    || isset($userGroupMap[$userGroup->getName()])
                ) {
                    return $this->wordpress->doShortCode($content);
                }
            }
        }

        return '';
    }
}
