<?php
/**
 * AdminUserGroupController.php
 *
 * The AdminUserGroupController class file.
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

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\Form\FormHelper;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class AdminUserGroupController
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
     * @var AccessHandler
     */
    private $accessHandler;

    /**
     * @var UserGroupFactory
     */
    private $userGroupFactory;

    /**
     * @var FormHelper
     */
    private $formHelper;

    /**
     * @var \UserAccessManager\UserGroup\UserGroup
     */
    private $userGroup = null;

    /**
     * AdminUserGroupController constructor.
     *
     * @param Php              $php
     * @param Wordpress        $wordpress
     * @param MainConfig       $config
     * @param AccessHandler    $accessHandler
     * @param UserGroupFactory $userGroupFactory
     * @param FormHelper       $formHelper
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        MainConfig $config,
        AccessHandler $accessHandler,
        UserGroupFactory $userGroupFactory,
        FormHelper $formHelper
    ) {
        parent::__construct($php, $wordpress, $config);
        $this->accessHandler = $accessHandler;
        $this->userGroupFactory = $userGroupFactory;
        $this->formHelper = $formHelper;
    }

    /**
     * Returns the tab groups.
     *
     * @return array
     */
    public function getTabGroups()
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
     *
     * @param string $key
     *
     * @return string
     */
    public function getGroupText($key)
    {
        return $this->formHelper->getText($key);
    }

    /**
     * Returns the translated tag group section name by the given key.
     *
     * @param string $key
     *
     * @return string
     */
    public function getGroupSectionText($key)
    {
        $objects = $this->wordpress->getPostTypes(['public' => true], 'objects')
            + $this->wordpress->getTaxonomies(['public' => true], 'objects');

        $objectName = $key;

        if ($objectName === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
            $objectName = TXT_UAM_USER;
        } elseif (isset($objects[$key]) === true) {
            $objectName = $objects[$key]->labels->name;

            if ($objects[$key] instanceof \WP_Post_Type) {
                $objectName .= ' ('.TXT_UAM_POST_TYPE.')';
            } elseif ($objects[$key] instanceof \WP_Taxonomy) {
                $objectName .= ' ('.TXT_UAM_TAXONOMY_TYPE.')';
            }
        }

        return $objectName;
    }

    /**
     * Returns the a user group object.
     *
     * @return \UserAccessManager\UserGroup\UserGroup
     */
    public function getUserGroup()
    {
        if ($this->userGroup === null) {
            $userGroupId = $this->getRequestParameter('userGroupId');
            $this->userGroup = $this->userGroupFactory->createUserGroup($userGroupId);
        }

        return $this->userGroup;
    }

    /**
     * Returns all user groups.
     *
     * @return \UserAccessManager\UserGroup\UserGroup[]
     */
    public function getUserGroups()
    {
        return $this->accessHandler->getUserGroups();
    }

    /**
     * Returns the wordpress role names.
     *
     * @return array
     */
    public function getRoleNames()
    {
        $roles = $this->wordpress->getRoles();
        return $roles->role_names;
    }

    /**
     * Action to insert or update a user group.
     */
    public function insertUpdateUserGroupAction()
    {
        $this->verifyNonce(self::INSERT_UPDATE_GROUP_NONCE);

        $userGroupId = $this->getRequestParameter('userGroupId');

        $userGroup = $this->userGroupFactory->createUserGroup($userGroupId);

        // Assign parameters
        $groupName = $this->getRequestParameter('userGroupName');

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
                $this->userGroup = $userGroup;
                $this->setUpdateMessage(TXT_UAM_GROUP_ADDED);
            } else {
                $this->setUpdateMessage(TXT_UAM_USER_GROUP_EDIT_SUCCESS);
            }

            $this->accessHandler->addUserGroup($userGroup);
        }
    }

    /**
     * Action to delete user groups.
     */
    public function deleteUserGroupAction()
    {
        $this->verifyNonce(self::DELETE_GROUP_NONCE);
        $userGroups = $this->getRequestParameter('delete', []);

        foreach ($userGroups as $id) {
            $this->accessHandler->deleteUserGroup($id);
        }

        $this->setUpdateMessage(TXT_UAM_DELETE_GROUP);
    }

    /**
     * Action to set default user groups.
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
            $userGroupInfo = isset($defaultUserGroups[$userGroupId]) === true ? $defaultUserGroups[$userGroupId] : [];

            if (isset($userGroupInfo['id']) === true
                && (string)$userGroupInfo['id'] === (string)$userGroupId
            ) {
                $userGroup->addDefaultType(
                    $objectType,
                    empty($userGroupInfo['fromTime']) === false ? $userGroupInfo['fromTime'] : null,
                    empty($userGroupInfo['toTime']) === false ? $userGroupInfo['toTime'] : null
                );
            }
        }

        $this->setUpdateMessage(TXT_UAM_SET_DEFAULT_USER_GROUP_SUCCESS);
    }
}
