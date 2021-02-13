<?php
/**
 * UserGroupController.php
 *
 * The UserGroupController class file.
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

namespace UserAccessManager\Controller\Backend;

use Exception;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\Form\FormHelper;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;
use WP_Post_Type;
use WP_Taxonomy;

/**
 * Class UserGroupController
 *
 * @package UserAccessManager\Controller
 */
class UserGroupController extends Controller
{
    use ControllerTabNavigationTrait;

    const INSERT_UPDATE_GROUP_NONCE = 'uamInsertUpdateGroup';
    const DELETE_GROUP_NONCE = 'uamDeleteGroup';
    const SET_DEFAULT_USER_GROUPS_NONCE = 'uamSetDefaultUserGroups';
    const GROUP_USER_GROUPS = 'user_groups';
    const GROUP_DEFAULT_USER_GROUPS = 'default_user_groups';
    const DEFAULT_USER_GROUPS_FORM_FIELD = 'default_user_groups';

    /**
     * @var string
     */
    protected $template = 'AdminUserGroup.php';

    /**
     * @var UserGroupHandler
     */
    private $userGroupHandler;

    /**
     * @var UserGroupFactory
     */
    private $userGroupFactory;

    /**
     * @var FormHelper
     */
    private $formHelper;

    /**
     * @var UserGroup
     */
    private $userGroup = null;

    /**
     * UserGroupController constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param WordpressConfig $wordpressConfig
     * @param UserGroupHandler $userGroupHandler
     * @param UserGroupFactory $userGroupFactory
     * @param FormHelper $formHelper
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        UserGroupHandler $userGroupHandler,
        UserGroupFactory $userGroupFactory,
        FormHelper $formHelper
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
        $this->userGroupHandler = $userGroupHandler;
        $this->userGroupFactory = $userGroupFactory;
        $this->formHelper = $formHelper;
    }

    /**
     * Returns the tab groups.
     * @return array
     */
    public function getTabGroups(): array
    {
        return [
            self::GROUP_USER_GROUPS => ['user_groups'],
            self::GROUP_DEFAULT_USER_GROUPS => array_merge(
                array_keys($this->wordpress->getPostTypes(['public' => true], 'objects')),
                array_keys($this->wordpress->getTaxonomies(['public' => true], 'objects')),
                [ObjectHandler::GENERAL_USER_OBJECT_TYPE]
            )
        ];
    }

    /**
     * Returns the translated tag group name by the given key.
     * @param string $key
     * @return string
     */
    public function getGroupText(string $key): string
    {
        return $this->formHelper->getText($key);
    }

    /**
     * Returns the translated tag group section name by the given key.
     * @param string $key
     * @return string
     */
    public function getGroupSectionText(string $key): string
    {
        $objects = $this->wordpress->getPostTypes(['public' => true], 'objects')
            + $this->wordpress->getTaxonomies(['public' => true], 'objects');

        $objectName = $key;

        if ($objectName === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
            $objectName = TXT_UAM_USER;
        } elseif (isset($objects[$key]) === true) {
            $objectName = $objects[$key]->labels->name;

            if ($objects[$key] instanceof WP_Post_Type) {
                $objectName .= ' (' . TXT_UAM_POST_TYPE . ')';
            } elseif ($objects[$key] instanceof WP_Taxonomy) {
                $objectName .= ' (' . TXT_UAM_TAXONOMY_TYPE . ')';
            }
        }

        return $objectName;
    }

    /**
     * Returns the a user group object.
     * @return UserGroup
     * @throws UserGroupTypeException
     */
    public function getUserGroup(): UserGroup
    {
        if ($this->userGroup === null) {
            $userGroupId = $this->getRequestParameter('userGroupId');
            $this->userGroup = $this->userGroupFactory->createUserGroup($userGroupId);
        }

        return $this->userGroup;
    }

    /**
     * Returns the sort url.
     * @param string $sort
     * @return string
     */
    public function getSortUrl(string $sort): string
    {
        $requestUrl = $this->getRequestUrl();
        $requestUrl = preg_replace('/&amp;orderby[^&]*/i', '', $requestUrl);
        $requestUrl = preg_replace('/&amp;order[^&]*/i', '', $requestUrl);
        $divider = strpos($requestUrl, '?') === false ? '?' : '&amp;';
        $order = (string) $this->getRequestParameter('order') === 'asc' ? 'desc' : 'asc';
        return "{$requestUrl}{$divider}orderby={$sort}&amp;order={$order}";
    }

    /**
     * Returns all user groups.
     * @return UserGroup[]
     * @throws UserGroupTypeException
     */
    public function getUserGroups(): array
    {
        $userGroups = $this->userGroupHandler->getUserGroups();

        $sort = $this->getRequestParameter('orderby');

        if ($sort !== null) {
            $reverse = (string) $this->getRequestParameter('order') === 'desc';

            uasort(
                $userGroups,
                function (UserGroup $userGroupOne, UserGroup $userGroupTwo) use ($sort, $reverse) {
                    $values = ['', ''];
                    $method = 'get' . ucfirst($sort);

                    if (method_exists($userGroupOne, $method) === true) {
                        $values = [(string) $userGroupOne->{$method}(), (string) $userGroupTwo->{$method}()];
                        $values = ($reverse === true) ? array_reverse($values) : $values;
                    }

                    return strnatcasecmp($values[0], $values[1]);
                }
            );
        }

        return $userGroups;
    }

    /**
     * Returns the wordpress role names.
     * @return array
     */
    public function getRoleNames(): array
    {
        $roles = $this->wordpress->getRoles();
        return $roles->role_names;
    }

    /**
     * Action to insert or update a user group.
     * @throws UserGroupTypeException
     * @throws Exception
     */
    public function insertUpdateUserGroupAction()
    {
        $this->verifyNonce(self::INSERT_UPDATE_GROUP_NONCE);

        $userGroupId = $this->getRequestParameter('userGroupId');

        $userGroup = $this->userGroupFactory->createUserGroup($userGroupId);

        // Assign parameters
        $groupName = (string) $this->getRequestParameter('userGroupName');

        if (trim($groupName) === '') {
            $this->setUpdateMessage(TXT_UAM_GROUP_NAME_ERROR);
            return;
        }

        $userGroup->setName($groupName);

        $userGroupDescription = $this->getRequestParameter('userGroupDescription');
        $userGroup->setDescription($userGroupDescription);

        $readAccess = $this->getRequestParameter('readAccess');
        $userGroup->setReadAccess($readAccess);

        $writeAccess = $this->getRequestParameter('writeAccess');
        $userGroup->setWriteAccess($writeAccess);

        $ipRange = $this->getRequestParameter('ipRange');
        $userGroup->setIpRange($ipRange);

        if ($userGroup->save() === true) {
            $roles = $this->getRequestParameter('roles', []);

            $userGroup->removeObject(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

            foreach ($roles as $role) {
                $userGroup->addObject(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, htmlentities($role));
            }

            if ($userGroupId === null) {
                $this->setUpdateMessage(TXT_UAM_GROUP_ADDED);
            } else {
                $this->setUpdateMessage(TXT_UAM_USER_GROUP_EDIT_SUCCESS);
            }

            $this->userGroupHandler->addUserGroup($userGroup);
        }
    }

    /**
     * Action to delete user groups.
     * @throws UserGroupTypeException
     */
    public function deleteUserGroupAction()
    {
        $this->verifyNonce(self::DELETE_GROUP_NONCE);
        $userGroups = $this->getRequestParameter('delete', []);

        foreach ($userGroups as $id) {
            $this->userGroupHandler->deleteUserGroup($id);
        }

        $this->setUpdateMessage(TXT_UAM_DELETE_GROUP);
    }

    /**
     * Checks if the default user group type should be added.
     * @param array $defaultUserGroups
     * @param string $userGroupId
     * @param null|string $fromTime
     * @param null|string $toTime
     * @return bool
     */
    private function isDefaultTypeAdd(
        array $defaultUserGroups,
        string $userGroupId,
        &$fromTime = null,
        &$toTime = null
    ): bool {
        $userGroupInfo = isset($defaultUserGroups[$userGroupId]) === true ? $defaultUserGroups[$userGroupId] : [];

        if (isset($userGroupInfo['id']) === true && (string) $userGroupInfo['id'] === (string) $userGroupId) {
            $fromTime = empty($userGroupInfo['fromTime']) === false ? $userGroupInfo['fromTime'] : null;
            $toTime = empty($userGroupInfo['toTime']) === false ? $userGroupInfo['toTime'] : null;
            return true;
        }

        return false;
    }

    /**
     * Action to set default user groups.
     * @throws UserGroupTypeException
     * @throws Exception
     */
    public function setDefaultUserGroupsAction()
    {
        $this->verifyNonce(self::SET_DEFAULT_USER_GROUPS_NONCE);
        $objectType = $this->getCurrentTabGroupSection();
        $defaultUserGroups = $this->getRequestParameter(self::DEFAULT_USER_GROUPS_FORM_FIELD, []);
        $userGroups = $this->getUserGroups();

        foreach ($userGroups as $userGroup) {
            $userGroupId = $userGroup->getId();
            $userGroup->removeDefaultType($objectType);

            if ($this->isDefaultTypeAdd($defaultUserGroups, $userGroupId, $fromTime, $toTime) === true) {
                $userGroup->addDefaultType($objectType, $fromTime, $toTime);
            }
        }

        $this->setUpdateMessage(TXT_UAM_SET_DEFAULT_USER_GROUP_SUCCESS);
    }
}
