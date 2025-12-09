<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Frontend;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Wordpress;

trait AdminOutputControllerTrait
{
    abstract protected function getWordpress(): Wordpress;
    abstract protected function getWordpressConfig(): WordpressConfig;
    abstract protected function getMainConfig(): MainConfig;
    abstract protected function getUtil(): Util;
    abstract protected function getUserHandler(): UserHandler;
    abstract protected function getUserGroupHandler(): UserGroupHandler;

    private function showAdminHint(): bool
    {
        return $this->getWordpressConfig()->atAdminPanel() === false
            && $this->getMainConfig()->blogAdminHint() === true;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function adminOutput(string $objectType, int|string|null $objectId, $text = null): string
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
