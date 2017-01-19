<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 16.01.17
 * Time: 23:21
 */
namespace UserAccessManager\Controller;

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserGroup\UserGroupFactory;
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
    protected $_oAccessHandler;

    /**
     * @var UserGroupFactory
     */
    protected $_oUserGroupFactory;

    /**
     * @var string
     */
    protected $_sTemplate = 'AdminUserGroup.php';

    /**
     * AdminUserGroupController constructor.
     *
     * @param Wordpress        $oWrapper
     * @param AccessHandler    $oAccessHandler
     * @param UserGroupFactory $oUserGroupFactory
     */
    public function __construct(Wordpress $oWrapper, AccessHandler $oAccessHandler, UserGroupFactory $oUserGroupFactory)
    {
        parent::__construct($oWrapper);
        $this->_oAccessHandler = $oAccessHandler;
        $this->_oUserGroupFactory = $oUserGroupFactory;
    }

    /**
     * Returns the a user group object.
     *
     * @return \UserAccessManager\UserGroup\UserGroup
     */
    public function getUserGroup()
    {
        $iUserGroupId = $this->getRequestParameter('userGroupId');
        return $this->_oUserGroupFactory->createUserGroup($iUserGroupId);
    }

    /**
     * Returns all user groups.
     *
     * @return \UserAccessManager\UserGroup\UserGroup[]
     */
    public function getUserGroups()
    {
        return $this->_oAccessHandler->getUserGroups();
    }

    /**
     * Returns the wordpress role names.
     *
     * @return array
     */
    public function getRoleNames()
    {
        $oRoles = $this->_oWrapper->getRoles();
        return $oRoles->role_names;
    }

    /**
     * Action to insert or update a user group.
     */
    public function insertUpdateUserGroupAction()
    {
        $this->_verifyNonce(self::INSERT_UPDATE_GROUP_NONCE);

        $iUserGroupId = $this->getRequestParameter('userGroupId');

        $oUserGroup = $this->_oUserGroupFactory->createUserGroup($iUserGroupId);

        // Assign parameters
        $sGroupName = $this->getRequestParameter('userGroupName');
        $oUserGroup->setGroupName($sGroupName);

        if (trim($sGroupName) === '') {
            $this->_setUpdateMessage(TXT_UAM_GROUP_NAME_ERROR);
            return;
        }

        $sUserGroupDescription = $this->getRequestParameter('userGroupDescription');
        $oUserGroup->setGroupDesc($sUserGroupDescription);

        $sReadAccess = $this->getRequestParameter('readAccess');
        $oUserGroup->setReadAccess($sReadAccess);

        $sWriteAccess = $this->getRequestParameter('writeAccess');
        $oUserGroup->setWriteAccess($sWriteAccess);

        $sIpRange = $this->getRequestParameter('ipRange');
        $oUserGroup->setIpRange($sIpRange);

        $aRoles = $this->getRequestParameter('roles', array());

        $oUserGroup->unsetObjects(ObjectHandler::ROLE_OBJECT_TYPE, true);

        foreach ($aRoles as $sRole) {
            $oUserGroup->addObject(ObjectHandler::ROLE_OBJECT_TYPE, htmlentities($sRole));
        }

        if ($oUserGroup->save() === true) {
            if ($iUserGroupId === null) {
                $this->_setUpdateMessage(TXT_UAM_GROUP_ADDED);
            } else {
                $this->_setUpdateMessage(TXT_UAM_ACCESS_GROUP_EDIT_SUC);
            }

            $this->_oAccessHandler->addUserGroup($oUserGroup);
        }
    }

    /**
     * Action to delete user groups.
     */
    public function deleteUserGroupAction()
    {
        $this->_verifyNonce(self::DELETE_GROUP_NONCE);
        $aUserGroups = $this->getRequestParameter('delete', array());

        foreach ($aUserGroups as $sId) {
            $this->_oAccessHandler->deleteUserGroup($sId);
        }

        $this->_setUpdateMessage(TXT_UAM_DEL_GROUP);
    }
}