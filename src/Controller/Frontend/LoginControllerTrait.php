<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Frontend;

use UserAccessManager\Wrapper\Wordpress;

trait LoginControllerTrait
{
    abstract protected function getWordpress(): Wordpress;
    abstract public function getRequestUrl(): string;
    abstract public function getRequestParameter(string $name, mixed $default = null): mixed;

    public function getUserLogin(): string
    {
        $userLogin = $this->getRequestParameter('log');
        return $this->getWordpress()->escHtml(stripslashes((string) $userLogin));
    }

    public function isUserLoggedIn(): bool
    {
        return $this->getWordpress()->isUserLoggedIn();
    }

    public function getCurrentUserName(): string
    {
        return $this->getWordpress()->getCurrentUser()->display_name;
    }

    public function getLoginUrl(): string
    {
        return $this->getWordpress()->wpLoginUrl($this->getRequestUrl());
    }

    public function getLogoutUrl(): string
    {
        return $this->getWordpress()->wpLogoutUrl($this->getRequestUrl());
    }

    public function getRegistrationUrl(): string
    {
        return $this->getWordpress()->wpRegistrationUrl();
    }

    public function getLostPasswordUrl(): string
    {
        return $this->getWordpress()->wpLostPasswordUrl($this->getRequestUrl());
    }

    public function showLoginForm(): bool
    {
        return $this->getWordpress()->isSingle() === true || $this->getWordpress()->isPage() === true;
    }

    public function getRedirectLoginUrl(): mixed
    {
        $loginUrl = $this->getWordpress()->getBlogInfo('wpurl')
            .'/wp-login.php?redirect_to='.urlencode($_SERVER['REQUEST_URI']);
        return $this->getWordpress()->applyFilters('uam_login_url', $loginUrl);
    }
}
