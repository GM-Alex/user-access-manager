<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 20.01.17
 * Time: 01:34
 */

namespace UserAccessManager\Controller;


use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Config\Config;
use UserAccessManager\Database\Database;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\Wrapper\Wordpress;

class AdminObjectController extends Controller
{
    const COLUMN_NAME = 'uam_access';

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
    protected $_aFullObjectUserGroups = array();

    /**
     * @var UserGroup[]
     */
    protected $_aObjectUserGroups = array();

    /**
     * @var int
     */
    protected $_iUserGroupDiff = 0;

    /**
     * AdminObjectController constructor.
     *
     * @param Wordpress     $oWrapper
     * @param Config        $oConfig
     * @param Database      $oDatabase
     * @param ObjectHandler $oObjectHandler
     * @param AccessHandler $oAccessHandler
     */
    public function __construct(
        Wordpress $oWrapper,
        Config $oConfig,
        Database $oDatabase,
        ObjectHandler $oObjectHandler,
        AccessHandler $oAccessHandler
    )
    {
        parent::__construct($oWrapper, $oConfig);
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
        $this->_aFullObjectUserGroups = $this->_oAccessHandler->getUserGroupsForObject($sObjectType, $iObjectId, false);
        $this->_aObjectUserGroups = $this->_oAccessHandler->getUserGroupsForObject($sObjectType, $iObjectId);
        $this->_iUserGroupDiff = count($this->_aFullObjectUserGroups) - count($this->_aObjectUserGroups);
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
    public function getFullObjectUserGroups()
    {
        return $this->_aObjectUserGroups;
    }

    /**
     * Returns the current object user groups.
     *
     * @return UserGroup[]
     */
    public function getObjectUserGroups()
    {
        return $this->_aObjectUserGroups;
    }

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
     * Checks if the current user is an admin.
     *
     * @return bool
     */
    public function isCurrentUserAdmin()
    {
        if ($this->_sObjectType !== ObjectHandler::GENERAL_USER_OBJECT_TYPE
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
        $oRoles = $this->_oWrapper->getRoles();
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

    public function getRecursiveMemberShip()
    {
        //TODO
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
    protected function _saveObjectData($sObjectType, $iObjectId, $aUserGroups = null)
    {
        $aFormData = array();

        if (isset($_POST['uam_update_groups'])) {
            $aFormData = $_POST;
        } elseif (isset($_GET['uam_update_groups'])) {
            $aFormData = $_GET;
        }

        if (isset($aFormData['uam_update_groups'])
            && ($this->_oAccessHandler->checkUserAccess('manage_user_groups')
                || $this->_oConfig->authorsCanAddPostsToGroups() === true)
        ) {
            if ($aUserGroups === null) {
                $aUserGroups = (isset($aFormData['uam_user_groups']) && is_array($aFormData['uam_user_groups']))
                    ? $aFormData['uam_user_groups'] : array();
            }

            $aAddUserGroups = array_flip($aUserGroups);
            $aRemoveUserGroups = $this->_oAccessHandler->getUserGroupsForObject($sObjectType, $iObjectId);
            $aUamUserGroups = $this->_oAccessHandler->getUserGroups();

            if (isset($aFormData['uam_bulk_type'])) {
                $sBulkType = $aFormData['uam_bulk_type'];

                if ($sBulkType === 'remove') {
                    $aRemoveUserGroups = $aAddUserGroups;
                    $aAddUserGroups = array();
                }
            }

            foreach ($aUamUserGroups as $sGroupId => $oUamUserGroup) {
                if (isset($aRemoveUserGroups[$sGroupId])) {
                    $oUamUserGroup->removeObject($sObjectType, $iObjectId);
                }

                if (isset($aAddUserGroups[$sGroupId])) {
                    $oUamUserGroup->addObject($sObjectType, $iObjectId);
                }

                $oUamUserGroup->save();
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
                '%s',
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
     * @param mixed $mPostParam The post _iId or a array of a post.
     */
    public function savePostData($mPostParam)
    {
        if (is_array($mPostParam)) {
            $oPost = $this->_oObjectHandler->getPost($mPostParam['ID']);
        } else {
            $oPost = $this->_oObjectHandler->getPost($mPostParam);
        }

        $iPostId = $oPost->ID;
        $sPostType = $oPost->post_type;

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
    } /*
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
        $this->_setObjectInformation(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $iId);

        if ($sColumnName === self::COLUMN_NAME) {
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

    /**
     * Shows the error if the user has no rights to edit the content.
     */
    public function noRightsToEditContent()
    {
        $blNoRights = false;

        $sPostId = $this->getRequestParameter('post');

        if (is_numeric($sPostId)) {
            $oPost = $this->_oObjectHandler->getPost($sPostId);
            $blNoRights = !$this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID);
        }

        $sAttachmentId = $this->getRequestParameter('attachment_id');

        if ($blNoRights === false && is_numeric($sAttachmentId)) {
            $oPost = $this->_oObjectHandler->getPost($sAttachmentId);
            $blNoRights = !$this->_oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID);
        }

        $sTagId = $this->getRequestParameter('tag_ID');

        if ($blNoRights === false && is_numeric($sTagId)) {
            $blNoRights = !$this->_oAccessHandler->checkObjectAccess(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $sTagId);
        }

        if ($blNoRights === true) {
            $this->_oWrapper->wpDie(TXT_UAM_NO_RIGHTS);
        }
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
    public function savePlObjectData($sObjectType, $iObjectId, $aUserGroups = null)
    {
        $this->_saveObjectData($sObjectType, $iObjectId, $aUserGroups);
    }

    /**
     * The function for the pluggable remove action.
     *
     * @param string  $sObjectName The name of the pluggable object.
     * @param integer $iObjectId   The pluggable object id.
     */
    public function removePlObjectData($sObjectName, $iObjectId)
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
    public function showPlGroupSelectionForm($sObjectType, $iObjectId)
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
    public function getPlColumn($sObjectType, $iObjectId)
    {
        $this->_setObjectInformation($sObjectType, $iObjectId);
        return $this->_getIncludeContents('ObjectColumn.php');
    }

}