<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

trait ControllerTabNavigationTrait
{
    abstract public function getRequestUrl(): string;

    abstract public function getRequestParameter(string $name, $default = null): mixed;

    abstract public function getGroupText(string $key, bool $description = false): string;

    abstract public function getGroupSectionText(string $key): string;

    abstract public function getTabGroups(): array;

    public function getCurrentTabGroup(): string
    {
        $groups = $this->getTabGroups();
        $keys = array_keys($groups);
        $default = (string) reset($keys);
        $group = (string) $this->getRequestParameter('tab_group', $default);

        return isset($groups[$group]) === true ? $group : $default;
    }

    public function getSections(): array
    {
        $groups = $this->getTabGroups();

        return $groups[$this->getCurrentTabGroup()] ?? [];
    }

    public function getCurrentTabGroupSection(): string
    {
        $groups = $this->getTabGroups();
        $sections = (array) ($groups[$this->getCurrentTabGroup()] ?? reset($groups));
        $default = (string) reset($sections);
        $section = (string) $this->getRequestParameter('tab_group_section', $default);

        return in_array($section, $sections, true) === true ? $section : $default;
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
