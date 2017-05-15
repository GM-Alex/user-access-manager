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
 * @version   SVN: $id$
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
    const DEFAULT_GROUPS_FORM_NAME = 'uam_user_groups';
    const UPDATE_GROUPS_FORM_NAME = 'uam_update_groups';

    /**
     * @var Database
     */
    private $database;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var AccessHandler
     */
    private $accessHandler;

    /**
     * @var null|string
     */
    private $groupsFromName = null;

    /**
     * @var null|string
     */
    private $objectType = null;

    /**
     * @var null|string
     */
    private $objectId = null;

    /**
     * @var UserGroup[]
     */
    private $objectUserGroups = [];

    /**
     * @var int
     */
    private $userGroupDiff = 0;

    /**
     * AdminObjectController constructor.
     *
     * @param Php           $php
     * @param Wordpress     $wordpress
     * @param Config        $config
     * @param Database      $database
     * @param ObjectHandler $objectHandler
     * @param AccessHandler $accessHandler
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Config $config,
        Database $database,
        ObjectHandler $objectHandler,
        AccessHandler $accessHandler
    ) {
        parent::__construct($php, $wordpress, $config);
        $this->database = $database;
        $this->objectHandler = $objectHandler;
        $this->accessHandler = $accessHandler;
    }

    /**
     * Sets the current object type, the object id and the user groups.
     *
     * @param string $objectType
     * @param string $objectId
     * @param array  $objectUserGroups
     */
    private function setObjectInformation($objectType, $objectId, array $objectUserGroups = null)
    {
        $this->objectType = $objectType;
        $this->objectId = $objectId;

        if ($objectUserGroups === null) {
            $objectUserGroups = $this->accessHandler->getFilteredUserGroupsForObject($objectType, $objectId);
            $fullObjectUserGroups = $this->accessHandler->getUserGroupsForObject($objectType, $objectId);
            $this->userGroupDiff = count($fullObjectUserGroups) - count($objectUserGroups);
        } else {
            $this->userGroupDiff = 0;
        }

        $this->objectUserGroups = $objectUserGroups;
    }

    /**
     * Returns the default groups form name.
     *
     * @return string
     */
    public function getGroupsFromName()
    {
        return ($this->groupsFromName !== null) ? (string)$this->groupsFromName : self::DEFAULT_GROUPS_FORM_NAME;
    }

    /**
     * Returns the current object type.
     *
     * @return string
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * Returns the current object id.
     *
     * @return string
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Returns the current object user groups.
     *
     * @return UserGroup[]
     */
    public function getObjectUserGroups()
    {
        return $this->objectUserGroups;
    }

    /**
     * Returns the user group count diff.
     *
     * @return int
     */
    public function getUserGroupDiff()
    {
        return $this->userGroupDiff;
    }

    /**
     * Returns all available user groups.
     *
     * @return UserGroup[]
     */
    public function getUserGroups()
    {
        return $this->accessHandler->getUserGroups();
    }

    /**
     * Returns the filtered user groups.
     *
     * @return UserGroup[]
     */
    public function getFilteredUserGroups()
    {
        return $this->accessHandler->getFilteredUserGroups();
    }

    /**
     * Checks if the current user is an admin.
     *
     * @return bool
     */
    public function isCurrentUserAdmin()
    {
        if ($this->objectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE
            && $this->objectId !== null
        ) {
            return $this->accessHandler->userIsAdmin($this->objectId);
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
        $roles = $this->wordpress->getRoles();
        return $roles->role_names;
    }

    /**
     * Returns all object types.
     *
     * @return array
     */
    public function getAllObjectTypes()
    {
        return $this->objectHandler->getAllObjectTypes();
    }

    /**
     * Checks the user access.
     *
     * @return bool
     */
    public function checkUserAccess()
    {
        return $this->accessHandler->checkUserAccess();
    }

    /**
     * Returns the recursive object membership.
     *
     * @param $userGroup
     *
     * @return array
     */
    public function getRecursiveMembership(UserGroup $userGroup)
    {
        $recursiveMembership = [];
        $objectId = $this->getObjectId();
        $objectType = $this->getObjectType();
        $roles = $this->getRoleNames();
        $recursiveMembershipForObject = $userGroup->getRecursiveMembershipForObject($objectType, $objectId);

        foreach ($recursiveMembershipForObject as $recursiveType => $objectIds) {
            foreach ($objectIds as $objectId) {
                $objectName = $objectId;
                $typeName = $this->objectHandler->getGeneralObjectType($recursiveType);

                if ($typeName === ObjectHandler::GENERAL_ROLE_OBJECT_TYPE) {
                    $objectName = isset($roles[$objectId]) ? $roles[$objectId] : $objectId;
                } elseif ($typeName === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
                    $user = $this->objectHandler->getUser($objectId);
                    $objectName = ($user !== false) ? $user->display_name : $objectId;
                } elseif ($typeName === ObjectHandler::GENERAL_TERM_OBJECT_TYPE) {
                    $term = $this->objectHandler->getTerm($objectId);

                    if ($term !== false) {
                        $taxonomy = $this->wordpress->getTaxonomy($term->taxonomy);
                        $typeName = ($taxonomy !== false) ? $taxonomy->labels->name : $typeName;
                        $objectName = $term->name;
                    }
                } elseif ($typeName === ObjectHandler::GENERAL_POST_OBJECT_TYPE) {
                    $post = $this->objectHandler->getPost($objectId);

                    if ($post !== false) {
                        $postTypeObject = $this->wordpress->getPostTypeObject($post->post_type);
                        $typeName = ($postTypeObject !== null) ? $postTypeObject->labels->name : $typeName;
                        $objectName = $post->post_title;
                    }
                } elseif ($this->objectHandler->isPluggableObject($recursiveType) === true) {
                    $pluggableObject = $this->objectHandler->getPluggableObject($recursiveType);
                    $typeName = $pluggableObject->getObjectType();
                    $objectName = $pluggableObject->getObjectName($objectId);
                }

                if ($typeName !== null) {
                    $recursiveMembership[$typeName][$objectId] = $objectName;
                }
            }
        }

        return $recursiveMembership;
    }

    /**
     * Shows the error if the user has no rights to edit the content.
     */
    public function checkRightsToEditContent()
    {
        $noRights = false;

        $postId = $this->getRequestParameter('post');
        $postId = is_numeric($postId) === false ? $this->getRequestParameter('attachment_id') : $postId;

        if (is_numeric($postId) === true) {
            $post = $this->objectHandler->getPost($postId);

            if ($post !== false) {
                $noRights = !$this->accessHandler->checkObjectAccess($post->post_type, $post->ID);
            }
        }

        $tagId = $this->getRequestParameter('tag_ID');

        if ($noRights === false && is_numeric($tagId)) {
            $noRights = !$this->accessHandler->checkObjectAccess(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $tagId);
        }

        if ($noRights === true) {
            $this->wordpress->wpDie(TXT_UAM_NO_RIGHTS_MESSAGE, TXT_UAM_NO_RIGHTS_TITLE, ['response' => 403]);
        }
    }


    /*
     * Meta functions
     */

    /**
     * Saves the object data to the database.
     *
     * @param string      $objectType The object type.
     * @param integer     $objectId   The _iId of the object.
     * @param UserGroup[] $userGroups The new user groups for the object.
     */
    private function saveObjectData($objectType, $objectId, array $userGroups = null)
    {
        $isUpdateForm = (bool)$this->getRequestParameter(self::UPDATE_GROUPS_FORM_NAME, false) === true
            || $this->getRequestParameter('uam_bulk_type') !== null;

        $hasRights = $this->accessHandler->checkUserAccess('manage_user_groups') === true
            || $this->config->authorsCanAddPostsToGroups() === true;

        if ($isUpdateForm === true && $hasRights === true) {
            if ($userGroups === null) {
                $updateGroups = $this->getRequestParameter(self::DEFAULT_GROUPS_FORM_NAME, []);
                $userGroups = (is_array($updateGroups) === true) ? $updateGroups : [];
            }

            $addUserGroups = array_flip($userGroups);
            $filteredUserGroupsForObject = $this->accessHandler->getFilteredUserGroupsForObject(
                $objectType,
                $objectId
            );
            $removeUserGroups = array_flip(array_keys($filteredUserGroupsForObject));
            $filteredUserGroups = $this->accessHandler->getFilteredUserGroups();
            $bulkType = $this->getRequestParameter('uam_bulk_type');

            if ($bulkType === self::BULK_REMOVE) {
                $removeUserGroups = $addUserGroups;
                $addUserGroups = [];
            }

            foreach ($filteredUserGroups as $groupId => $userGroup) {
                if (isset($removeUserGroups[$groupId]) === true) {
                    $userGroup->removeObject($objectType, $objectId);
                }

                if (isset($addUserGroups[$groupId]) === true) {
                    $userGroup->addObject($objectType, $objectId);
                }

                $userGroup->save();
            }

            $this->accessHandler->unsetUserGroupsForObject();
        }
    }

    /**
     * Removes the object data.
     *
     * @param string $objectType The object type.
     * @param int    $id         The object id.
     */
    private function removeObjectData($objectType, $id)
    {
        $this->database->delete(
            $this->database->getUserGroupToObjectTable(),
            [
                'object_id' => $id,
                'object_type' => $objectType,
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
     * @param array $defaults The table headers.
     *
     * @return array
     */
    public function addPostColumnsHeader($defaults)
    {
        $defaults[self::COLUMN_NAME] = TXT_UAM_COLUMN_ACCESS;
        return $defaults;
    }

    /**
     * The function for the manage_users_custom_column action.
     *
     * @param string  $columnName The column name.
     * @param integer $id         The id.
     */
    public function addPostColumn($columnName, $id)
    {
        if ($columnName === self::COLUMN_NAME) {
            $post = $this->objectHandler->getPost($id);
            $this->setObjectInformation($post->post_type, $post->ID);
            echo $this->getIncludeContents('ObjectColumn.php');
        }
    }

    /**
     * The function for the uma_post_access meta box.
     *
     * @param object $post The post.
     */
    public function editPostContent($post)
    {
        if ($post instanceof \WP_Post) {
            $this->setObjectInformation($post->post_type, $post->ID);
        }

        echo $this->getIncludeContents('PostEditForm.php');
    }

    /**
     * Adds the bulk edit form.
     *
     * @param $columnName
     */
    public function addBulkAction($columnName)
    {
        if ($columnName === self::COLUMN_NAME) {
            echo $this->getIncludeContents('BulkEditForm.php');
        }
    }

    /**
     * The function for the save_post action.
     *
     * @param mixed $postParam The post id or a array of a post.
     */
    public function savePostData($postParam)
    {
        $postId = (is_array($postParam) === true) ? $postParam['ID'] : $postParam;
        $post = $this->objectHandler->getPost($postId);
        $postType = $post->post_type;
        $postId = $post->ID;

        if ($postType === 'revision') {
            $postId = $post->post_parent;
            $parentPost = $this->objectHandler->getPost($postId);
            $postType = $parentPost->post_type;
        }

        $this->saveObjectData($postType, $postId);
    }

    /**
     * The function for the attachment_fields_to_save filter.
     * We have to use this because the attachment actions work
     * not in the way we need.
     *
     * @param array $attachment The attachment id.
     *
     * @return array
     */
    public function saveAttachmentData($attachment)
    {
        $this->savePostData($attachment['ID']);

        return $attachment;
    }

    /**
     * The function for the wp_ajax_save_attachment_compat filter.
     */
    public function saveAjaxAttachmentData()
    {
        $attachmentId = $this->getRequestParameter('id');
        $userGroups = $this->getRequestParameter(self::DEFAULT_GROUPS_FORM_NAME);

        $this->saveObjectData(
            ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            $attachmentId,
            $userGroups
        );
    }

    /**
     * The function for the delete_post action.
     *
     * @param integer $postId The post id.
     */
    public function removePostData($postId)
    {
        $post = $this->objectHandler->getPost($postId);
        $this->removeObjectData($post->post_type, $postId);
    }

    /**
     * The function for the media_meta action.
     *
     * @param array    $formFields The meta.
     * @param \WP_Post $post       The post.
     *
     * @return string
     */
    public function showMediaFile(array $formFields, $post = null)
    {
        $attachmentId = $this->getRequestParameter('attachment_id');

        if ($attachmentId !== null) {
            $post = $this->objectHandler->getPost($attachmentId);
        }

        if ($post instanceof \WP_Post) {
            $this->setObjectInformation($post->post_type, $post->ID);
        }

        $formFields[self::DEFAULT_GROUPS_FORM_NAME] =[
            'label' => TXT_UAM_SET_UP_USER_GROUPS,
            'input' => 'editFrom',
            'editFrom' => $this->getIncludeContents('MediaAjaxEditForm.php')
        ];

        return $formFields;
    }

    /*
     * Functions for the user actions.
     */

    /**
     * The function for the manage_users_columns filter.
     *
     * @param array $defaults The table headers.
     *
     * @return array
     */
    public function addUserColumnsHeader($defaults)
    {
        $defaults[self::COLUMN_NAME] = TXT_UAM_COLUMN_USER_GROUPS;
        return $defaults;
    }

    /**
     * The function for the manage_users_custom_column action.
     *
     * @param string  $return     The normal return value.
     * @param string  $columnName The column name.
     * @param integer $id         The id.
     *
     * @return string|null
     */
    public function addUserColumn($return, $columnName, $id)
    {
        if ($columnName === self::COLUMN_NAME) {
            $this->setObjectInformation(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $id);
            $return .= $this->getIncludeContents('UserColumn.php');
        }

        return $return;
    }

    /**
     * The function for the edit_user_profile action.
     */
    public function showUserProfile()
    {
        $userId = $this->getRequestParameter('user_id');

        if ($userId !== null) {
            $this->setObjectInformation(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $userId);
        }

        echo $this->getIncludeContents('UserProfileEditForm.php');
    }

    /**
     * The function for the profile_update action.
     *
     * @param integer $userId The user id.
     */
    public function saveUserData($userId)
    {
        $this->saveObjectData(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $userId);
    }

    /**
     * The function for the delete_user action.
     *
     * @param integer $userId The user id.
     */
    public function removeUserData($userId)
    {
        $this->removeObjectData(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $userId);
    }


    /*
     * Functions for the term actions.
     */

    /**
     * The function for the manage_categories_columns filter.
     *
     * @param array $defaults The table headers.
     *
     * @return array
     */
    public function addTermColumnsHeader($defaults)
    {
        $defaults[self::COLUMN_NAME] = TXT_UAM_COLUMN_ACCESS;
        return $defaults;
    }

    /**
     * The function for the manage_categories_custom_column action.
     *
     * @param string  $content    Content for the column. Multiple filter calls are possible, so we need to append.
     * @param string  $columnName The column name.
     * @param integer $id         The id.
     *
     * @return string $content with content appended for self::COLUMN_NAME column
     */
    public function addTermColumn($content, $columnName, $id)
    {
        if ($columnName === self::COLUMN_NAME) {
            $this->setObjectInformation(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $id);
            $content .= $this->getIncludeContents('ObjectColumn.php');
        }

        return $content;
    }

    /**
     * The function for the edit_{term}_form action.
     *
     * @param \WP_Term $term The term.
     */
    public function showTermEditForm($term)
    {
        if ($term instanceof \WP_Term) {
            $this->setObjectInformation($term->taxonomy, $term->term_id);
        }

        echo $this->getIncludeContents('TermEditForm.php');
    }

    /**
     * The function for the edit_{term} action.
     *
     * @param integer $termId The term id.
     */
    public function saveTermData($termId)
    {
        $this->saveObjectData(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $termId);
    }

    /**
     * The function for the delete_{term} action.
     *
     * @param integer $termId The id of the term.
     */
    public function removeTermData($termId)
    {
        $this->removeObjectData(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $termId);
    }

    /*
     * Functions for the pluggable object actions.
     */

    /**
     * The function for the pluggable save action.
     *
     * @param string      $objectType The name of the pluggable object.
     * @param integer     $objectId   The pluggable object id.
     * @param UserGroup[] $userGroups The user groups for the object.
     */
    public function savePluggableObjectData($objectType, $objectId, $userGroups = null)
    {
        $this->saveObjectData($objectType, $objectId, $userGroups);
    }

    /**
     * The function for the pluggable remove action.
     *
     * @param string  $objectName The name of the pluggable object.
     * @param integer $objectId   The pluggable object id.
     */
    public function removePluggableObjectData($objectName, $objectId)
    {
        $this->removeObjectData($objectName, $objectId);
    }

    /**
     * Returns the group selection form for pluggable objects.
     *
     * @param string $objectType       The object type.
     * @param string $objectId         The id of the object.
     * @param string $formName         The formName.
     * @param array  $objectUserGroups If set we force this user groups for the object.
     *
     * @return string
     */
    public function showPluggableGroupSelectionForm(
        $objectType,
        $objectId,
        $formName = null,
        array $objectUserGroups = null
    ) {
        $this->setObjectInformation($objectType, $objectId, $objectUserGroups);

        $this->groupsFromName = $formName;
        $formContent = $this->getIncludeContents('GroupSelectionForm.php');
        $this->groupsFromName = null;

        return $formContent;
    }

    /**
     * Returns the column for a pluggable object.
     *
     * @param string $objectType The object type.
     * @param string $objectId   The object id.
     *
     * @return string
     */
    public function getPluggableColumn($objectType, $objectId)
    {
        $this->setObjectInformation($objectType, $objectId);
        return $this->getIncludeContents('ObjectColumn.php');
    }
}
