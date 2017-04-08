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
 * @version   SVN: $Id$
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
     * @var AccessHandler
     */
    protected $AccessHandler;

    /**
     * @var UserGroupFactory
     */
    protected $UserGroupFactory;

    /**
     * @var string
     */
    protected $sTemplate = 'AdminUserGroup.php';

    /**
     * @var \UserAccessManager\UserGroup\UserGroup
     */
    protected $UserGroup = null;

    /**
     * AdminUserGroupController constructor.
     *
     * @param Php              $Php
     * @param Wordpress        $Wordpress
     * @param Config           $Config
     * @param AccessHandler    $AccessHandler
     * @param UserGroupFactory $UserGroupFactory
     */
    public function __construct(
        Php $Php,
        Wordpress $Wordpress,
        Config $Config,
        AccessHandler $AccessHandler,
        UserGroupFactory $UserGroupFactory
    ) {
        parent::__construct($Php, $Wordpress, $Config);
        $this->AccessHandler = $AccessHandler;
        $this->UserGroupFactory = $UserGroupFactory;
    }

    /**
     * Returns the a user group object.
     *
     * @return \UserAccessManager\UserGroup\UserGroup
     */
    public function getUserGroup()
    {
        if ($this->UserGroup === null) {
            $iUserGroupId = $this->getRequestParameter('userGroupId');
            $this->UserGroup = $this->UserGroupFactory->createUserGroup($iUserGroupId);
        }

        return $this->UserGroup;
    }

    /**
     * Returns all user groups.
     *
     * @return \UserAccessManager\UserGroup\UserGroup[]
     */
    public function getUserGroups()
    {
        return $this->AccessHandler->getUserGroups();
    }

    /**
     * Returns the wordpress role names.
     *
     * @return array
     */
    public function getRoleNames()
    {
        $Roles = $this->Wordpress->getRoles();
        return $Roles->role_names;
    }

    /**
     * Action to insert or update a user group.
     */
    public function insertUpdateUserGroupAction()
    {
        $this->verifyNonce(self::INSERT_UPDATE_GROUP_NONCE);

        $iUserGroupId = $this->getRequestParameter('userGroupId');

        $UserGroup = $this->UserGroupFactory->createUserGroup($iUserGroupId);

        // Assign parameters
        $sGroupName = $this->getRequestParameter('userGroupName');

        if (trim($sGroupName) === '') {
            $this->setUpdateMessage(TXT_UAM_GROUP_NAME_ERROR);
            return;
        }

        $UserGroup->setName($sGroupName);

        $sUserGroupDescription = $this->getRequestParameter('userGroupDescription');
        $UserGroup->setDescription($sUserGroupDescription);

        $sReadAccess = $this->getRequestParameter('readAccess');
        $UserGroup->setReadAccess($sReadAccess);

        $sWriteAccess = $this->getRequestParameter('writeAccess');
        $UserGroup->setWriteAccess($sWriteAccess);

        $sIpRange = $this->getRequestParameter('ipRange');
        $UserGroup->setIpRange($sIpRange);

        $aRoles = $this->getRequestParameter('roles', []);

        $UserGroup->removeObject(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

        foreach ($aRoles as $sRole) {
            $UserGroup->addObject(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, htmlentities($sRole));
        }

        if ($UserGroup->save() === true) {
            if ($iUserGroupId === null) {
                $this->UserGroup = $UserGroup;
                $this->setUpdateMessage(TXT_UAM_GROUP_ADDED);
            } else {
                $this->setUpdateMessage(TXT_UAM_ACCESS_GROUP_EDIT_SUCCESS);
            }

            $this->AccessHandler->addUserGroup($UserGroup);
        }
    }

    /**
     * Action to delete user groups.
     */
    public function deleteUserGroupAction()
    {
        $this->verifyNonce(self::DELETE_GROUP_NONCE);
        $aUserGroups = $this->getRequestParameter('delete', []);

        foreach ($aUserGroups as $sId) {
            $this->AccessHandler->deleteUserGroup($sId);
        }

        $this->setUpdateMessage(TXT_UAM_DELETE_GROUP);
    }
}
