<?php
/**
 * ControllerTabNavigationTrait.php
 *
 * The ControllerTabNavigationTrait class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller\Backend;

/**
 * Trait ControllerTabNavigationTrait
 *
 * @package UserAccessManager\Controller
 */
trait ControllerTabNavigationTrait
{
    /**
     * Returns the current request url.
     *
     * @return string
     */
    abstract public function getRequestUrl();

    /**
     * Returns the request parameter.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    abstract public function getRequestParameter($name, $default = null);

    /**
     * Translates the given group by the group key.
     *
     * @param string $key
     *
     * @return string
     */
    abstract public function getGroupText($key);

    /**
     * Translates the given group section by the group key.
     *
     * @param string $key
     *
     * @return string
     */
    abstract public function getGroupSectionText($key);

    /**
     * Returns the tab groups.
     *
     * @return array
     */
    abstract public function getTabGroups();

    /**
     * Returns the current tab group.
     *
     * @return string
     */
    public function getCurrentTabGroup()
    {
        $groups = $this->getTabGroups();
        $keys = array_keys($groups);

        return (string)$this->getRequestParameter('tab_group', reset($keys));
    }

    /**
     * Returns the tab group sections.
     *
     * @return array
     */
    public function getSections()
    {
        $groups = $this->getTabGroups();
        $group = $this->getCurrentTabGroup();

        return isset($groups[$group]) === true ? $groups[$group] : [];
    }

    /**
     * Returns the current tab group section.
     *
     * @return string
     */
    public function getCurrentTabGroupSection()
    {
        $groups = $this->getTabGroups();
        $group = $this->getCurrentTabGroup();

        if (isset($groups[$group]) === true) {
            $default = reset($groups[$group]);
        } else {
            $firstGroup = reset($groups);
            $default = reset($firstGroup);
        }

        return (string)$this->getRequestParameter('tab_group_section', $default);
    }

    /**
     * Returns the settings group link by the given group key.
     *
     * @param string $groupKey
     *
     * @return string
     */
    public function getTabGroupLink($groupKey)
    {
        $rawUrl = $this->getRequestUrl();
        $url = preg_replace('/&amp;tab_group[^&]*/i', '', $rawUrl);
        return $url.'&tab_group='.$groupKey;
    }

    /**
     * Returns the settings section link by the given group and section key.
     *
     * @param string $groupKey
     * @param string $sectionKey
     *
     * @return string
     */
    public function getTabGroupSectionLink($groupKey, $sectionKey)
    {
        $rawUrl = $this->getTabGroupLink($groupKey);
        $url = preg_replace('/&amp;tab_group_section[^&]*/i', '', $rawUrl);
        return $url.'&tab_group_section='.$sectionKey;
    }
}
