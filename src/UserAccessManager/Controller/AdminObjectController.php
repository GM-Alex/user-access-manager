<?php
/**
 * AdminObjectController.php
 *
 * The AdminObjectController class file.
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
use UserAccessManager\Database\Database;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class AdminObjectController
 *
 * @package UserAccessManager\Controller
 */
class AdminObjectController extends Controller
{
    const COLUMN_NAME = 'uam_access';
    const BULK_REMOVE = 'remove';

    /**
     * @var Database
     */
    protected $_oDatabase;

    /**
     * @var ObjectHandler
     */
    protected $_oObjectHandler;

    /**
     * @var AccessHandler
     */
    protected $_oAccessHandler;

    /**
     * @var string
     */
    protected $_sObjectType = null;

    /**
     * @var string
     */
    protected $_sObjectId = null;

    /**
     * @var UserGroup[]
     */
    protected $_aObjectUserGroups = array();

    /**
     * @var UserGroup[]
     */
    protected $_aFilteredObjectUserGroups = array();

    /**
     * @var int
     */
    protected $_iUserGroupDiff = 0;

    /**
     * AdminObjectController constructor.
     *
     * @param Php           $oPhp
     * @param Wordpress     $oWordpress
     * @param Config        $oConfig
     * @param Database      $oDatabase
     * @param ObjectHandler $oObjectHandler
     * @param AccessHandler $oAccessHandler
     */
    public function __construct(
        Php $oPhp,
        Wordpress $oWordpress,
        Config $oConfig,
        Database $oDatabase,
        ObjectHandler $oObjectHandler,
        AccessHandler $oAccessHandler
    )
    {
        parent::__construct($oPhp, $oWordpress, $oConfig);
        $this->_oDatabase = $oDatabase;
        $this->_oObjectHandler = $oObjectHandler;
        $this->_oAccessHandler = $oAccessHandler;
    }

    /**
     * Sets the current object type, the object id and the user groups.
     *
     * @param string $sObjectType
     * @param string $iObjectId
     */
    protected function _setObjectInformation($sObjectType, $iObjectId)
    {
        $this->_sObjectType = $sObjectType;
        $this->_sObjectId = $iObjectId;
        $this->_aObjectUserGroups = $this->_oAccessHandler->getUserGroupsForObject($sObjectType, $iObjectId);
        $this->_aFilteredObjectUserGroups = $this->_oAccessHandler->getFilteredUserGroupsForObject($sObjectType, $iObjectId);
        $this->_iUserGroupDiff = count($this->_aObjectUserGroups) - count($this->_aFilteredObjectUserGroups);
    }

    /**
     * Returns the current object type.
     *
     * @return string
     */
    public function getObjectType()
    {
        return $this->_sObjectType;
    }

    /**
     * Returns the current object id.
     *
     * @return string
     */
    public function getObjectId()
    {
        return $this->_sObjectId;
    }

    /**
     * Returns the current object full user groups.
     *
     * @return UserGroup[]
     */
    public function getObjectUserGroups()
    {
        return $this->_aObjectUserGroups;
    }

    /**
     * Returns the current object user groups.
     *
     * @return UserGroup[]
     */
    public function getFilteredObjectUserGroups()
    {
        return $this->_aFilteredObjectUserGroups;
    }

    /**
     * Returns the user group count diff.
     *
     * @return int
     */
    public function getUserGroupDiff()
    {
        return $this->_iUserGroupDiff;
    }

    /**
     * Returns all available user groups.
     *
     * @return UserGroup[]
     */
    public function getUserGroups()
    {
        return $this->_oAccessHandler->getUserGroups();
    }

    /**
     * Returns the filtered user groups.
     *
     * @return UserGroup[]
     */
    public function getFilteredUserGroups()
    {
        return $this->_oAccessHandler->getFilteredUserGroups();
    }

    /**
     * Checks if the current user is an admin.
     *
     * @return bool
     */
    public function isCurrentUserAdmin()
    {
        if ($this->_sObjectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE
            && $this->_sObjectId !== null
        ) {
            return $this->_oAccessHandler->userIsAdmin($this->_sObjectId);
        }

        return false;
    }

    /**
     * Returns the wordpress role names.
     *
     * @return array
     */
    public function getRoleNames()
    {
        $oRoles = $this->_oWordpress->getRoles();
        return $oRoles->role_names;
    }

    /**
     * Returns all object types.
     *
     * @return array
     */
    public function getAllObjectTypes()
    {
        return $this->_oObjectHandler->getAllObjectTypes();
    }

    /**
     * Checks the user access.
     *
     * @return bool
     */
    public function checkUserAccess()
    {
        return $this->_oAccessHandler->checkUserAccess();
    }

    /**
     * Returns the recursive object membership.
     *
     * @param $oUserGroup
     *
     * @return array
     */
    public function getRecursiveMembership(UserGroup $oUserGroup)
    {
        $aRecursiveMembership = array();
        $sObjectId = $this->getObjectId();
        $sObjectType = $this->getObjectType();
        $aRoles = $this->getRoleNames();
        $aRecursiveMembershipForObject = $oUserGroup->getRecursiveMembershipForObject($sObjectType, $sObjectId);

        foreach ($aRecursiveMembershipForObject as $sRecursiveType => $aObjectIds) {
            foreach ($aObjectIds as $sObjectId) {
                $sObjectName = $sObjectId;
                $sTypeName = $this->_oObjectHandler->getGeneralObjectType($sRecursiveType);

                if ($sTypeName === ObjectHandler::GENERAL_ROLE_OBJECT_TYPE) {
                    $sObjectName = isset($aRoles[$sObjectId]) ? $aRoles[$sObjectId] : $sObjectId;
                } elseif ($sTypeName === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
                    $oUser = $this->_oObjectHandler->getUser($sObjectId);
                    $sObjectName = ($oUser !== false) ? $oUser->display_name : $sObjectId;
                } elseif ($sTypeName === ObjectHandler::GENERAL_TERM_OBJECT_TYPE) {
                    $oTerm = $this->_oObjectHandler->getTerm($sObjectId);

                    if ($oTerm !== false) {
                        $oTaxonomy = $this->_oWordpress->getTaxonomy($oTerm->taxonomy);
                        $sTypeName = ($oTaxonomy !== false) ? $oTaxonomy->labels->name : $sTypeName;
                        $sObjectName = $oTerm->name;
                    }
                } elseif ($sTypeName === ObjectHandler::GENERAL_POST_OBJECT_TYPE) {
                    $oPost = $this->_oObjectHandler->getPost($sObjectId);

                    if ($oPost !== false) {
                        $oPostTypeObject = $this->_oWordpress->getPostTypeObject($oPost->post_type);
                        $sTypeName = ($oPostTypeObject !== null) ? $oPostTypeObject->labels->name : $sTypeName;
                        $sObjectName = $oPost->post_title;
                    }
                } elseif ($this->_oObjectHandler->isPluggableObject($sRecursiveType) === true) {
                    $oPluggableObject = $this->_oObjectHandler->getPluggableObject($sRecursiveType);
                    $sTypeName = $oPluggableObject->getName();
                    $sObjectName = $oPluggableObject->getObjectName($sObjectId);
                }

                if ($sTypeName !== null) {
                    $aRecursiveMembership[$sTypeName][$sObjectId] = $sObjectName;
                }
            }
        }

        return $aRecursiveMembership;
    }

    /**
     * Shows the error if the user has no rights to edit the content.
     */
    public function checkRightsToEditContent()
    {
        $blNoRights = false;

        $sPostId = $this->getRequestParameter('post');
        $sPostId = is_numeric($sPostId) === false ? $this->getRequestParameter('attachment_id') : $sPostId;

        if (is_numeric($sPostId) === true) {
            $oPost = $this->_oObjectHandler->getPost($sPostId);

            if ($oPost !== false) {
                $blNoRights = !$this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID);
            }
        }

        $sTagId = $this->getRequestParameter('tag_ID');

        if ($blNoRights === false && is_numeric($sTagId)) {
            $blNoRights = !$this->_oAccessHandler->checkObjectAccess(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $sTagId);
        }

        if ($blNoRights === true) {
            $this->_oWordpress->wpDie(TXT_UAM_NO_RIGHTS);
        }
    }


    /*
     * Meta functions
     */

    /**
     * Saves the object data to the database.
     *
     * @param string      $sObjectType The object type.
     * @param integer     $iObjectId   The _iId of the object.
     * @param UserGroup[] $aUserGroups The new user groups for the object.
     */
    protected function _saveObjectData($sObjectType, $iObjectId, array $aUserGroups = null)
    {
        if ($this->_oAccessHandler->checkUserAccess('manage_user_groups') === true
            || $this->_oConfig->authorsCanAddPostsToGroups() === true
        ) {
            if ($aUserGroups === null) {
                $aUpdateGroups = $this->getRequestParameter('uam_update_groups', array());
                $aUserGroups = (is_array($aUpdateGroups) === true) ? $aUpdateGroups : array();
            }

            $aAddUserGroups = array_flip($aUserGroups);
            $aFilteredUserGroupsForObject = $this->_oAccessHandler->getFilteredUserGroupsForObject(
                $sObjectType,
                $iObjectId
            );
            $aRemoveUserGroups = array_flip(array_keys($aFilteredUserGroupsForObject));
            $aFilteredUserGroups = $this->_oAccessHandler->getFilteredUserGroups();
            $sBulkType = $this->getRequestParameter('uam_bulk_type');

            if ($sBulkType === self::BULK_REMOVE) {
                $aRemoveUserGroups = $aAddUserGroups;
                $aAddUserGroups = array();
            }

            foreach ($aFilteredUserGroups as $sGroupId => $oUserGroup) {
                if (isset($aRemoveUserGroups[$sGroupId]) === true) {
                    $oUserGroup->removeObject($sObjectType, $iObjectId);
                }

                if (isset($aAddUserGroups[$sGroupId]) === true) {
                    $oUserGroup->addObject($sObjectType, $iObjectId);
                }

                $oUserGroup->save();
            }

            $this->_oAccessHandler->unsetUserGroupsForObject();
        }
    }

    /**
     * Removes the object data.
     *
     * @param string $sObjectType The object type.
     * @param int    $iId         The object id.
     */
    protected function _removeObjectData($sObjectType, $iId)
    {
        $this->_oDatabase->delete(
            $this->_oDatabase->getUserGroupToObjectTable(),
            array(
                'object_id' => $iId,
                'object_type' => $sObjectType,
            ),
            array(
                '%d',
                '%s'
            )
        );
    }

    /*
     * Functions for the post actions.
     */

    /**
     * The function for the manage_posts_columns and
     * the manage_pages_columns filter.
     *
     * @param array $aDefaults The table headers.
     *
     * @return array
     */
    public function addPostColumnsHeader($aDefaults)
    {
        $aDefaults[self::COLUMN_NAME] = TXT_UAM_COLUMN_ACCESS;
        return $aDefaults;
    }

    /**
     * The function for the manage_users_custom_column action.
     *
     * @param string  $sColumnName The column name.
     * @param integer $iId         The id.
     */
    public function addPostColumn($sColumnName, $iId)
    {
        if ($sColumnName === self::COLUMN_NAME) {
            $oPost = $this->_oObjectHandler->getPost($iId);
            $this->_setObjectInformation($oPost->post_type, $oPost->ID);
            echo $this->_getIncludeContents('ObjectColumn.php');
        }
    }

    /**
     * The function for the uma_post_access meta box.
     *
     * @param object $oPost The post.
     */
    public function editPostContent($oPost)
    {
        if ($oPost instanceof \WP_Post) {
            $this->_setObjectInformation($oPost->post_type, $oPost->ID);
        }

        echo $this->_getIncludeContents('PostEditForm.php');
    }

    /**
     * Adds the bulk edit form.
     *
     * @param $sColumnName
     */
    public function addBulkAction($sColumnName)
    {
        if ($sColumnName === self::COLUMN_NAME) {
            echo $this->_getIncludeContents('BulkEditForm.php');
        }
    }

    /**
     * The function for the save_post action.
     *
     * @param mixed $mPostParam The post id or a array of a post.
     */
    public function savePostData($mPostParam)
    {
        $iPostId = is_array($mPostParam) ? $mPostParam['ID'] : $mPostParam;
        $oPost = $this->_oObjectHandler->getPost($iPostId);
        $sPostType = $oPost->post_type;
        $iPostId = $oPost->ID;

        if ($sPostType === 'revision') {
            $iPostId = $oPost->post_parent;
            $oParentPost = $this->_oObjectHandler->getPost($iPostId);
            $sPostType = $oParentPost->post_type;
        }

        $this->_saveObjectData($sPostType, $iPostId);
    }

    /**
     * The function for the attachment_fields_to_save filter.
     * We have to use this because the attachment actions work
     * not in the way we need.
     *
     * @param array $aAttachment The attachment id.
     *
     * @return array
     */
    public function saveAttachmentData($aAttachment)
    {
        $this->savePostData($aAttachment['ID']);

        return $aAttachment;
    }

    /**
     * The function for the delete_post action.
     *
     * @param integer $iPostId The post id.
     */
    public function removePostData($iPostId)
    {
        $oPost = $this->_oObjectHandler->getPost($iPostId);
        $this->_removeObjectData($oPost->post_type, $iPostId);
    }

    /**
     * The function for the media_meta action.
     *
     * @param string $sMeta The meta.
     * @param object $oPost The post.
     *
     * @return string
     */
    public function showMediaFile($sMeta = '', $oPost = null)
    {
        $sAttachmentId = $this->getRequestParameter('attachment_id');

        if ($sAttachmentId !== null) {
            $oPost = $this->_oObjectHandler->getPost($sAttachmentId);
        }

        if ($oPost instanceof \WP_Post) {
            $this->_setObjectInformation($oPost->post_type, $oPost->ID);
        }

        $sContent = $sMeta;
        $sContent .= '</td></tr><tr>';
        $sContent .= '<th class="label">';
        $sContent .= '<label>'.TXT_UAM_SET_UP_USER_GROUPS.'</label>';
        $sContent .= '</th>';
        $sContent .= '<td class="field">';
        $sContent .= $this->_getIncludeContents('PostEditForm.php');

        return $sContent;
    }

    /*
     * Functions for the user actions.
     */

    /**
     * The function for the manage_users_columns filter.
     *
     * @param array $aDefaults The table headers.
     *
     * @return array
     */
    public function addUserColumnsHeader($aDefaults)
    {
        $aDefaults[self::COLUMN_NAME] = TXT_UAM_COLUMN_USER_GROUPS;
        return $aDefaults;
    }

    /**
     * The function for the manage_users_custom_column action.
     *
     * @param string  $sReturn     The normal return value.
     * @param string  $sColumnName The column name.
     * @param integer $iId         The id.
     *
     * @return string|null
     */
    public function addUserColumn($sReturn, $sColumnName, $iId)
    {
        if ($sColumnName === self::COLUMN_NAME) {
            $this->_setObjectInformation(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $iId);
            $sReturn .= $this->_getIncludeContents('UserColumn.php');
        }

        return $sReturn;
    }

    /**
     * The function for the edit_user_profile action.
     */
    public function showUserProfile()
    {
        $sUserId = $this->getRequestParameter('user_id');

        if ($sUserId !== null) {
            $this->_setObjectInformation(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $sUserId);
        }

        echo $this->_getIncludeContents('UserProfileEditForm.php');
    }

    /**
     * The function for the profile_update action.
     *
     * @param integer $iUserId The user id.
     */
    public function saveUserData($iUserId)
    {
        $this->_saveObjectData(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $iUserId);
    }

    /**
     * The function for the delete_user action.
     *
     * @param integer $iUserId The user id.
     */
    public function removeUserData($iUserId)
    {
        $this->_removeObjectData(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $iUserId);
    }


    /*
     * Functions for the term actions.
     */

    /**
     * The function for the manage_categories_columns filter.
     *
     * @param array $aDefaults The table headers.
     *
     * @return array
     */
    public function addTermColumnsHeader($aDefaults)
    {
        $aDefaults[self::COLUMN_NAME] = TXT_UAM_COLUMN_ACCESS;
        return $aDefaults;
    }

    /**
     * The function for the manage_categories_custom_column action.
     *
     * @param string  $sContent    Content for the column. Multiple filter calls are possible, so we need to append.
     * @param string  $sColumnName The column name.
     * @param integer $iId         The id.
     *
     * @return string $sContent with content appended for self::COLUMN_NAME column
     */
    public function addTermColumn($sContent, $sColumnName, $iId)
    {
        if ($sColumnName === self::COLUMN_NAME) {
            $this->_setObjectInformation(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $iId);
            $sContent .= $this->_getIncludeContents('ObjectColumn.php');
        }

        return $sContent;
    }

    /**
     * The function for the edit_{term}_form action.
     *
     * @param \WP_Term $oTerm The term.
     */
    public function showTermEditForm($oTerm)
    {
        if ($oTerm instanceof \WP_Term) {
            $this->_setObjectInformation($oTerm->taxonomy, $oTerm->term_id);
        }

        echo $this->_getIncludeContents('TermEditForm.php');
    }

    /**
     * The function for the edit_{term} action.
     *
     * @param integer $iTermId The term id.
     */
    public function saveTermData($iTermId)
    {
        $this->_saveObjectData(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $iTermId);
    }

    /**
     * The function for the delete_{term} action.
     *
     * @param integer $iTermId The id of the term.
     */
    public function removeTermData($iTermId)
    {
        $this->_removeObjectData(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $iTermId);
    }

    /*
     * Functions for the pluggable object actions.
     */

    /**
     * The function for the pluggable save action.
     *
     * @param string      $sObjectType The name of the pluggable object.
     * @param integer     $iObjectId   The pluggable object id.
     * @param UserGroup[] $aUserGroups The user groups for the object.
     */
    public function savePluggableObjectData($sObjectType, $iObjectId, $aUserGroups = null)
    {
        $this->_saveObjectData($sObjectType, $iObjectId, $aUserGroups);
    }

    /**
     * The function for the pluggable remove action.
     *
     * @param string  $sObjectName The name of the pluggable object.
     * @param integer $iObjectId   The pluggable object id.
     */
    public function removePluggableObjectData($sObjectName, $iObjectId)
    {
        $this->_removeObjectData($sObjectName, $iObjectId);
    }

    /**
     * Returns the group selection form for pluggable objects.
     *
     * @param string $sObjectType The object type.
     * @param string $iObjectId   The id of the object.
     *
     * @return string;
     */
    public function showPluggableGroupSelectionForm($sObjectType, $iObjectId)
    {
        $this->_setObjectInformation($sObjectType, $iObjectId);
        return $this->_getIncludeContents('GroupSelectionForm.php');
    }

    /**
     * Returns the column for a pluggable object.
     *
     * @param string $sObjectType The object type.
     * @param string $iObjectId   The object id.
     *
     * @return string
     */
    public function getPluggableColumn($sObjectType, $iObjectId)
    {
        $this->_setObjectInformation($sObjectType, $iObjectId);
        return $this->_getIncludeContents('ObjectColumn.php');
    }

}