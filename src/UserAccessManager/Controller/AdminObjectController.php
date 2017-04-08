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
    protected $oDatabase;

    /**
     * @var ObjectHandler
     */
    protected $oObjectHandler;

    /**
     * @var AccessHandler
     */
    protected $oAccessHandler;

    /**
     * @var string
     */
    protected $sObjectType = null;

    /**
     * @var string
     */
    protected $sObjectId = null;

    /**
     * @var UserGroup[]
     */
    protected $aObjectUserGroups = [];

    /**
     * @var UserGroup[]
     */
    protected $aFilteredObjectUserGroups = [];

    /**
     * @var int
     */
    protected $iUserGroupDiff = 0;

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
    ) {
        parent::__construct($oPhp, $oWordpress, $oConfig);
        $this->oDatabase = $oDatabase;
        $this->oObjectHandler = $oObjectHandler;
        $this->oAccessHandler = $oAccessHandler;
    }

    /**
     * Sets the current object type, the object id and the user groups.
     *
     * @param string $sObjectType
     * @param string $iObjectId
     */
    protected function setObjectInformation($sObjectType, $iObjectId)
    {
        $this->sObjectType = $sObjectType;
        $this->sObjectId = $iObjectId;
        $this->aObjectUserGroups = $this->oAccessHandler->getUserGroupsForObject($sObjectType, $iObjectId);
        $this->aFilteredObjectUserGroups = $this->oAccessHandler->getFilteredUserGroupsForObject(
            $sObjectType,
            $iObjectId
        );
        $this->iUserGroupDiff = count($this->aObjectUserGroups) - count($this->aFilteredObjectUserGroups);
    }

    /**
     * Returns the current object type.
     *
     * @return string
     */
    public function getObjectType()
    {
        return $this->sObjectType;
    }

    /**
     * Returns the current object id.
     *
     * @return string
     */
    public function getObjectId()
    {
        return $this->sObjectId;
    }

    /**
     * Returns the current object full user groups.
     *
     * @return UserGroup[]
     */
    public function getObjectUserGroups()
    {
        return $this->aObjectUserGroups;
    }

    /**
     * Returns the current object user groups.
     *
     * @return UserGroup[]
     */
    public function getFilteredObjectUserGroups()
    {
        return $this->aFilteredObjectUserGroups;
    }

    /**
     * Returns the user group count diff.
     *
     * @return int
     */
    public function getUserGroupDiff()
    {
        return $this->iUserGroupDiff;
    }

    /**
     * Returns all available user groups.
     *
     * @return UserGroup[]
     */
    public function getUserGroups()
    {
        return $this->oAccessHandler->getUserGroups();
    }

    /**
     * Returns the filtered user groups.
     *
     * @return UserGroup[]
     */
    public function getFilteredUserGroups()
    {
        return $this->oAccessHandler->getFilteredUserGroups();
    }

    /**
     * Checks if the current user is an admin.
     *
     * @return bool
     */
    public function isCurrentUserAdmin()
    {
        if ($this->sObjectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE
            && $this->sObjectId !== null
        ) {
            return $this->oAccessHandler->userIsAdmin($this->sObjectId);
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
        $oRoles = $this->oWordpress->getRoles();
        return $oRoles->role_names;
    }

    /**
     * Returns all object types.
     *
     * @return array
     */
    public function getAllObjectTypes()
    {
        return $this->oObjectHandler->getAllObjectTypes();
    }

    /**
     * Checks the user access.
     *
     * @return bool
     */
    public function checkUserAccess()
    {
        return $this->oAccessHandler->checkUserAccess();
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
        $aRecursiveMembership = [];
        $sObjectId = $this->getObjectId();
        $sObjectType = $this->getObjectType();
        $aRoles = $this->getRoleNames();
        $aRecursiveMembershipForObject = $oUserGroup->getRecursiveMembershipForObject($sObjectType, $sObjectId);

        foreach ($aRecursiveMembershipForObject as $sRecursiveType => $aObjectIds) {
            foreach ($aObjectIds as $sObjectId) {
                $sObjectName = $sObjectId;
                $sTypeName = $this->oObjectHandler->getGeneralObjectType($sRecursiveType);

                if ($sTypeName === ObjectHandler::GENERAL_ROLE_OBJECT_TYPE) {
                    $sObjectName = isset($aRoles[$sObjectId]) ? $aRoles[$sObjectId] : $sObjectId;
                } elseif ($sTypeName === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
                    $oUser = $this->oObjectHandler->getUser($sObjectId);
                    $sObjectName = ($oUser !== false) ? $oUser->display_name : $sObjectId;
                } elseif ($sTypeName === ObjectHandler::GENERAL_TERM_OBJECT_TYPE) {
                    $oTerm = $this->oObjectHandler->getTerm($sObjectId);

                    if ($oTerm !== false) {
                        $oTaxonomy = $this->oWordpress->getTaxonomy($oTerm->taxonomy);
                        $sTypeName = ($oTaxonomy !== false) ? $oTaxonomy->labels->name : $sTypeName;
                        $sObjectName = $oTerm->name;
                    }
                } elseif ($sTypeName === ObjectHandler::GENERAL_POST_OBJECT_TYPE) {
                    $oPost = $this->oObjectHandler->getPost($sObjectId);

                    if ($oPost !== false) {
                        $oPostTypeObject = $this->oWordpress->getPostTypeObject($oPost->post_type);
                        $sTypeName = ($oPostTypeObject !== null) ? $oPostTypeObject->labels->name : $sTypeName;
                        $sObjectName = $oPost->post_title;
                    }
                } elseif ($this->oObjectHandler->isPluggableObject($sRecursiveType) === true) {
                    $oPluggableObject = $this->oObjectHandler->getPluggableObject($sRecursiveType);
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
            $oPost = $this->oObjectHandler->getPost($sPostId);

            if ($oPost !== false) {
                $blNoRights = !$this->oAccessHandler->checkObjectAccess($oPost->post_type, $oPost->ID);
            }
        }

        $sTagId = $this->getRequestParameter('tag_ID');

        if ($blNoRights === false && is_numeric($sTagId)) {
            $blNoRights = !$this->oAccessHandler->checkObjectAccess(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $sTagId);
        }

        if ($blNoRights === true) {
            $this->oWordpress->wpDie(TXT_UAM_NO_RIGHTS);
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
    protected function saveObjectData($sObjectType, $iObjectId, array $aUserGroups = null)
    {
        if ($this->oAccessHandler->checkUserAccess('manage_user_groups') === true
            || $this->oConfig->authorsCanAddPostsToGroups() === true
        ) {
            if ($aUserGroups === null) {
                $aUpdateGroups = $this->getRequestParameter('uam_update_groups', []);
                $aUserGroups = (is_array($aUpdateGroups) === true) ? $aUpdateGroups : [];
            }

            $aAddUserGroups = array_flip($aUserGroups);
            $aFilteredUserGroupsForObject = $this->oAccessHandler->getFilteredUserGroupsForObject(
                $sObjectType,
                $iObjectId
            );
            $aRemoveUserGroups = array_flip(array_keys($aFilteredUserGroupsForObject));
            $aFilteredUserGroups = $this->oAccessHandler->getFilteredUserGroups();
            $sBulkType = $this->getRequestParameter('uam_bulk_type');

            if ($sBulkType === self::BULK_REMOVE) {
                $aRemoveUserGroups = $aAddUserGroups;
                $aAddUserGroups = [];
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

            $this->oAccessHandler->unsetUserGroupsForObject();
        }
    }

    /**
     * Removes the object data.
     *
     * @param string $sObjectType The object type.
     * @param int    $iId         The object id.
     */
    protected function removeObjectData($sObjectType, $iId)
    {
        $this->oDatabase->delete(
            $this->oDatabase->getUserGroupToObjectTable(),
            [
                'object_id' => $iId,
                'object_type' => $sObjectType,
            ],
            [
                '%d',
                '%s'
            ]
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
            $oPost = $this->oObjectHandler->getPost($iId);
            $this->setObjectInformation($oPost->post_type, $oPost->ID);
            echo $this->getIncludeContents('ObjectColumn.php');
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
            $this->setObjectInformation($oPost->post_type, $oPost->ID);
        }

        echo $this->getIncludeContents('PostEditForm.php');
    }

    /**
     * Adds the bulk edit form.
     *
     * @param $sColumnName
     */
    public function addBulkAction($sColumnName)
    {
        if ($sColumnName === self::COLUMN_NAME) {
            echo $this->getIncludeContents('BulkEditForm.php');
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
        $oPost = $this->oObjectHandler->getPost($iPostId);
        $sPostType = $oPost->post_type;
        $iPostId = $oPost->ID;

        if ($sPostType === 'revision') {
            $iPostId = $oPost->post_parent;
            $oParentPost = $this->oObjectHandler->getPost($iPostId);
            $sPostType = $oParentPost->post_type;
        }

        $this->saveObjectData($sPostType, $iPostId);
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
        $oPost = $this->oObjectHandler->getPost($iPostId);
        $this->removeObjectData($oPost->post_type, $iPostId);
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
            $oPost = $this->oObjectHandler->getPost($sAttachmentId);
        }

        if ($oPost instanceof \WP_Post) {
            $this->setObjectInformation($oPost->post_type, $oPost->ID);
        }

        $sContent = $sMeta;
        $sContent .= '</td></tr><tr>';
        $sContent .= '<th class="label">';
        $sContent .= '<label>'.TXT_UAM_SET_UP_USER_GROUPS.'</label>';
        $sContent .= '</th>';
        $sContent .= '<td class="field">';
        $sContent .= $this->getIncludeContents('PostEditForm.php');

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
            $this->setObjectInformation(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $iId);
            $sReturn .= $this->getIncludeContents('UserColumn.php');
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
            $this->setObjectInformation(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $sUserId);
        }

        echo $this->getIncludeContents('UserProfileEditForm.php');
    }

    /**
     * The function for the profile_update action.
     *
     * @param integer $iUserId The user id.
     */
    public function saveUserData($iUserId)
    {
        $this->saveObjectData(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $iUserId);
    }

    /**
     * The function for the delete_user action.
     *
     * @param integer $iUserId The user id.
     */
    public function removeUserData($iUserId)
    {
        $this->removeObjectData(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $iUserId);
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
            $this->setObjectInformation(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $iId);
            $sContent .= $this->getIncludeContents('ObjectColumn.php');
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
            $this->setObjectInformation($oTerm->taxonomy, $oTerm->term_id);
        }

        echo $this->getIncludeContents('TermEditForm.php');
    }

    /**
     * The function for the edit_{term} action.
     *
     * @param integer $iTermId The term id.
     */
    public function saveTermData($iTermId)
    {
        $this->saveObjectData(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $iTermId);
    }

    /**
     * The function for the delete_{term} action.
     *
     * @param integer $iTermId The id of the term.
     */
    public function removeTermData($iTermId)
    {
        $this->removeObjectData(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $iTermId);
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
        $this->saveObjectData($sObjectType, $iObjectId, $aUserGroups);
    }

    /**
     * The function for the pluggable remove action.
     *
     * @param string  $sObjectName The name of the pluggable object.
     * @param integer $iObjectId   The pluggable object id.
     */
    public function removePluggableObjectData($sObjectName, $iObjectId)
    {
        $this->removeObjectData($sObjectName, $iObjectId);
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
        $this->setObjectInformation($sObjectType, $iObjectId);
        return $this->getIncludeContents('GroupSelectionForm.php');
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
        $this->setObjectInformation($sObjectType, $iObjectId);
        return $this->getIncludeContents('ObjectColumn.php');
    }
}
