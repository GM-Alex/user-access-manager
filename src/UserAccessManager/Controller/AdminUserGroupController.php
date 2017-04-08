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
    protected $oAccessHandler;

    /**
     * @var UserGroupFactory
     */
    protected $oUserGroupFactory;

    /**
     * @var string
     */
    protected $sTemplate = 'AdminUserGroup.php';

    /**
     * @var \UserAccessManager\UserGroup\UserGroup
     */
    protected $oUserGroup = null;

    /**
     * AdminUserGroupController constructor.
     *
     * @param Php              $oPhp
     * @param Wordpress        $oWordpress
     * @param Config           $oConfig
     * @param AccessHandler    $oAccessHandler
     * @param UserGroupFactory $oUserGroupFactory
     */
    public function __construct(
        Php $oPhp,
        Wordpress $oWordpress,
        Config $oConfig,
        AccessHandler $oAccessHandler,
        UserGroupFactory $oUserGroupFactory
    ) {
        parent::__construct($oPhp, $oWordpress, $oConfig);
        $this->oAccessHandler = $oAccessHandler;
        $this->oUserGroupFactory = $oUserGroupFactory;
    }

    /**
     * Returns the a user group object.
     *
     * @return \UserAccessManager\UserGroup\UserGroup
     */
    public function getUserGroup()
    {
        if ($this->oUserGroup === null) {
            $iUserGroupId = $this->getRequestParameter('userGroupId');
            $this->oUserGroup = $this->oUserGroupFactory->createUserGroup($iUserGroupId);
        }

        return $this->oUserGroup;
    }

    /**
     * Returns all user groups.
     *
     * @return \UserAccessManager\UserGroup\UserGroup[]
     */
    public function getUserGroups()
    {
        return $this->oAccessHandler->getUserGroups();
    }

    /**
     * Returns the wordpress role names.
     *
     * @return array
     */
    public function getRoleNames()
    {
        $oRoles = $this->oWordpress->getRoles();
        return $oRoles->role_names;
    }

    /**
     * Action to insert or update a user group.
     */
    public function insertUpdateUserGroupAction()
    {
        $this->verifyNonce(self::INSERT_UPDATE_GROUP_NONCE);

        $iUserGroupId = $this->getRequestParameter('userGroupId');

        $oUserGroup = $this->oUserGroupFactory->createUserGroup($iUserGroupId);

        // Assign parameters
        $sGroupName = $this->getRequestParameter('userGroupName');

        if (trim($sGroupName) === '') {
            $this->setUpdateMessage(TXT_UAM_GROUP_NAME_ERROR);
            return;
        }

        $oUserGroup->setName($sGroupName);

        $sUserGroupDescription = $this->getRequestParameter('userGroupDescription');
        $oUserGroup->setDescription($sUserGroupDescription);

        $sReadAccess = $this->getRequestParameter('readAccess');
        $oUserGroup->setReadAccess($sReadAccess);

        $sWriteAccess = $this->getRequestParameter('writeAccess');
        $oUserGroup->setWriteAccess($sWriteAccess);

        $sIpRange = $this->getRequestParameter('ipRange');
        $oUserGroup->setIpRange($sIpRange);

        $aRoles = $this->getRequestParameter('roles', []);

        $oUserGroup->removeObject(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE);

        foreach ($aRoles as $sRole) {
            $oUserGroup->addObject(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, htmlentities($sRole));
        }

        if ($oUserGroup->save() === true) {
            if ($iUserGroupId === null) {
                $this->oUserGroup = $oUserGroup;
                $this->setUpdateMessage(TXT_UAM_GROUP_ADDED);
            } else {
                $this->setUpdateMessage(TXT_UAM_ACCESS_GROUP_EDIT_SUCCESS);
            }

            $this->oAccessHandler->addUserGroup($oUserGroup);
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
            $this->oAccessHandler->deleteUserGroup($sId);
        }

        $this->setUpdateMessage(TXT_UAM_DELETE_GROUP);
    }
}
