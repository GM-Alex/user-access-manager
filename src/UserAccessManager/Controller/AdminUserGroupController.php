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
namespace UserAccessManager\Controller;

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Config\Config;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class AdminUserGroupController
 *
 * @package UserAccessManager\Controller
 */
class AdminUserGroupController extends Controller
{
    const INSERT_UPDATE_GROUP_NONCE = 'uamInsertUpdateGroup';
    const DELETE_GROUP_NONCE = 'uamDeleteGroup';

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
     * @var \UserAccessManager\UserGroup\UserGroup
     */
    private $userGroup = null;

    /**
     * AdminUserGroupController constructor.
     *
     * @param Php              $php
     * @param Wordpress        $wordpress
     * @param Config           $config
     * @param AccessHandler    $accessHandler
     * @param UserGroupFactory $userGroupFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Config $config,
        AccessHandler $accessHandler,
        UserGroupFactory $userGroupFactory
    ) {
        parent::__construct($php, $wordpress, $config);
        $this->accessHandler = $accessHandler;
        $this->userGroupFactory = $userGroupFactory;
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
                $this->setUpdateMessage(TXT_UAM_ACCESS_GROUP_EDIT_SUCCESS);
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
}
