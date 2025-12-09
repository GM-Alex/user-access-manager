<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

/**
 * Trait ControllerTabNavigationTrait
 *
 * @package UserAccessManager\Controller
 */
trait ControllerTabNavigationTrait
{
    abstract public function getRequestUrl(): string;

    abstract public function getRequestParameter(string $name, $default = null): mixed;

    abstract public function getGroupText(string $key): string;

    abstract public function getGroupSectionText(string $key): string;

    abstract public function getTabGroups(): array;

    public function getCurrentTabGroup(): string
    {
        $groups = $this->getTabGroups();
        $keys = array_keys($groups);

        return (string) $this->getRequestParameter('tab_group', reset($keys));
    }

    public function getSections(): array
    {
        $groups = $this->getTabGroups();
        $group = $this->getCurrentTabGroup();

        return isset($groups[$group]) === true ? $groups[$group] : [];
    }

    public function getCurrentTabGroupSection(): string
    {
        $groups = $this->getTabGroups();
        $group = $this->getCurrentTabGroup();

        if (isset($groups[$group]) === true) {
            $default = reset($groups[$group]);
        } else {
            $firstGroup = reset($groups);
            $default = reset($firstGroup);
        }

        return (string) $this->getRequestParameter('tab_group_section', $default);
    }

    public function getTabGroupLink(string $groupKey): string
    {
        $rawUrl = $this->getRequestUrl();
        $url = preg_replace('/&amp;tab_group[^&]*/i', '', $rawUrl);
        return $url . '&tab_group=' . $groupKey;
    }

    public function getTabGroupSectionLink(string $groupKey, string $sectionKey): string
    {
        $rawUrl = $this->getTabGroupLink($groupKey);
        $url = preg_replace('/&amp;tab_group_section[^&]*/i', '', $rawUrl);
        return $url . '&tab_group_section=' . $sectionKey;
    }
}
