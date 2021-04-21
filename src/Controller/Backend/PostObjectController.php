<?php
/**
 * PostObjectController.php
 *
 * The PostObjectController class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
use WP_Post;

/**
 * Class PostObjectController
 *
 * @package UserAccessManager\Controller\Backend
 */
class PostObjectController extends ObjectController
{
    /**
     * The function for the manage_posts_columns and
     * the manage_pages_columns filter.
     * @param array $defaults The table headers.
     * @return array
     */
    public function addPostColumnsHeader(array $defaults): array
    {
        $defaults[self::COLUMN_NAME] = TXT_UAM_COLUMN_ACCESS;
        return $defaults;
    }

    /**
     * The function for the manage_users_custom_column action.
     * @param string $columnName The column name.
     * @param int|string $id The id.
     * @throws UserGroupTypeException
     */
    public function addPostColumn(string $columnName, $id)
    {
        if ($columnName === self::COLUMN_NAME) {
            $post = $this->objectHandler->getPost($id);
            echo $this->getGroupColumn($post->post_type, $post->ID);
        }
    }

    /**
     * The function for the uam_post_access meta box.
     * @param mixed $post The post.
     * @throws UserGroupTypeException
     */
    public function editPostContent($post)
    {
        if ($post instanceof WP_Post) {
            $this->setObjectInformation($post->post_type, $post->ID);
        }

        echo $this->getIncludeContents('PostEditForm.php');
    }

    /**
     * Adds the bulk edit form.
     * @param $columnName
     */
    public function addBulkAction($columnName)
    {
        if ($columnName === self::COLUMN_NAME) {
            $this->getObjectInformation()->setObjectId(null);
            echo $this->getIncludeContents('BulkEditForm.php');
        }
    }

    /**
     * The function for the save_post action.
     * @param mixed $postParam The post id or a array of a post.
     * @throws UserGroupTypeException
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
     * The function for the add_attachment action.
     * @param int $postId
     * @throws UserGroupTypeException
     */
    public function addAttachment(int $postId)
    {
        $post = $this->objectHandler->getPost($postId);
        $postType = $post->post_type;
        $postId = $post->ID;
        $defaultGroups = [];

        foreach ($this->userGroupHandler->getFullUserGroups() as $userGroup) {
            if ($userGroup->isDefaultGroupForObjectType($postType) === true) {
                $defaultGroups[$userGroup->getId()] = ['id' => $userGroup->getId()];
            }
        }

        $this->saveObjectData($postType, $postId, $defaultGroups, true);
    }

    /**
     * The function for the attachment_fields_to_save filter.
     * We have to use this because the attachment actions work
     * not in the way we need.
     * @param array $attachment The attachment id.
     * @return array
     * @throws UserGroupTypeException
     */
    public function saveAttachmentData(array $attachment): array
    {
        $this->savePostData($attachment['ID']);

        return $attachment;
    }

    /**
     * The function for the wp_ajax_save_attachment_compat filter.
     * @throws UserGroupTypeException
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
     * @param int|string $postId The post id.
     */
    public function removePostData($postId)
    {
        $post = $this->objectHandler->getPost($postId);
        $this->removeObjectData($post->post_type, $postId);
    }

    /**
     * The function for the media_meta action.
     * @param array $formFields The meta.
     * @param WP_Post $post The post.
     * @return array
     * @throws UserGroupTypeException
     */
    public function showMediaFile(array $formFields, $post = null): array
    {
        if ($this->getRequestParameter('action') !== 'edit') {
            $attachmentId = $this->getRequestParameter('attachment_id');

            if ($attachmentId !== null) {
                $post = $this->objectHandler->getPost($attachmentId);
            }

            if ($post instanceof WP_Post) {
                $this->setObjectInformation($post->post_type, $post->ID);
            }

            $formFields[self::DEFAULT_GROUPS_FORM_NAME] = [
                'label' => TXT_UAM_SET_UP_USER_GROUPS,
                'input' => 'editFrom',
                'editFrom' => $this->getIncludeContents('MediaAjaxEditForm.php')
            ];
        }

        return $formFields;
    }
}
