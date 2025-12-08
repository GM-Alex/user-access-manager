<?php

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

class UserGroupController extends Controller
{
    use ControllerTabNavigationTrait;

    public const INSERT_UPDATE_GROUP_NONCE = 'uamInsertUpdateGroup';
    public const DELETE_GROUP_NONCE = 'uamDeleteGroup';
    public const SET_DEFAULT_USER_GROUPS_NONCE = 'uamSetDefaultUserGroups';
    public const GROUP_USER_GROUPS = 'user_groups';
    public const GROUP_DEFAULT_USER_GROUPS = 'default_user_groups';
    public const DEFAULT_USER_GROUPS_FORM_FIELD = 'default_user_groups';

    protected ?string $template = 'AdminUserGroup.php';
    private ?UserGroup $userGroup = null;

    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        private UserGroupHandler $userGroupHandler,
        private UserGroupFactory $userGroupFactory,
        private FormHelper $formHelper
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
    }

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

    public function getGroupText(string $key): string
    {
        return $this->formHelper->getText($key);
    }

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

    public function getSortUrl(string $sort): string
    {
        $requestUrl = $this->getRequestUrl();
        $requestUrl = preg_replace('/&amp;orderby[^&]*/i', '', $requestUrl);
        $requestUrl = preg_replace('/&amp;order[^&]*/i', '', $requestUrl);
        $divider = !str_contains($requestUrl, '?') ? '?' : '&amp;';
        $order = (string) $this->getRequestParameter('order') === 'asc' ? 'desc' : 'asc';
        return "$requestUrl{$divider}orderby=$sort&amp;order=$order";
    }

    /**
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

    public function getRoleNames(): array
    {
        $roles = $this->wordpress->getRoles();
        return $roles->role_names;
    }

    /**
     * @throws UserGroupTypeException
     * @throws Exception
     */
    public function insertUpdateUserGroupAction(): void
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
     * @throws UserGroupTypeException
     */
    public function deleteUserGroupAction(): void
    {
        $this->verifyNonce(self::DELETE_GROUP_NONCE);
        $userGroups = $this->getRequestParameter('delete', []);

        foreach ($userGroups as $id) {
            $this->userGroupHandler->deleteUserGroup($id);
        }

        $this->setUpdateMessage(TXT_UAM_DELETE_GROUP);
    }

    private function isDefaultTypeAdd(
        array $defaultUserGroups,
        string $userGroupId,
        string &$fromTime = null,
        string &$toTime = null
    ): bool {
        $userGroupInfo = isset($defaultUserGroups[$userGroupId]) === true ? $defaultUserGroups[$userGroupId] : [];

        if (isset($userGroupInfo['id']) === true && (string) $userGroupInfo['id'] === $userGroupId) {
            $fromTime = empty($userGroupInfo['fromTime']) === false ? $userGroupInfo['fromTime'] : null;
            $toTime = empty($userGroupInfo['toTime']) === false ? $userGroupInfo['toTime'] : null;
            return true;
        }

        return false;
    }

    /**
     * @throws UserGroupTypeException
     * @throws Exception
     */
    public function setDefaultUserGroupsAction(): void
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
