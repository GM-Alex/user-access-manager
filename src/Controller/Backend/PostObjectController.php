<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
use WP_Post;

class PostObjectController extends ObjectController
{
    public function addPostColumnsHeader(array $defaults): array
    {
        $defaults[self::COLUMN_NAME] = TXT_UAM_COLUMN_ACCESS;
        return $defaults;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function addPostColumn(string $columnName, int|string|null $id): void
    {
        if ($columnName === self::COLUMN_NAME) {
            $post = $this->objectHandler->getPost($id);
            echo $this->getGroupColumn($post->post_type, $post->ID);
        }
    }

    /**
     * @throws UserGroupTypeException
     */
    public function editPostContent(mixed $post): void
    {
        if ($post instanceof WP_Post) {
            $this->setObjectInformation($post->post_type, $post->ID);
        }

        echo $this->getIncludeContents('PostEditForm.php');
    }

    public function addBulkAction(string $columnName): void
    {
        if ($columnName === self::COLUMN_NAME) {
            $this->getObjectInformation()->setObjectId(null);
            echo $this->getIncludeContents('BulkEditForm.php');
        }
    }

    /**
     * @throws UserGroupTypeException
     */
    public function savePostData(mixed $postParam): void
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
     * @throws UserGroupTypeException
     */
    public function addAttachment(int $postId): void
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
     * @throws UserGroupTypeException
     */
    public function saveAttachmentData(array $attachment): array
    {
        $this->savePostData($attachment['ID']);

        return $attachment;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function saveAjaxAttachmentData(): void
    {
        $attachmentId = $this->getRequestParameter('id');
        $userGroups = $this->getRequestParameter(self::DEFAULT_GROUPS_FORM_NAME);

        $this->saveObjectData(
            ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            $attachmentId,
            $userGroups
        );
    }

    public function removePostData(int|string|null $postId): void
    {
        $post = $this->objectHandler->getPost($postId);
        $this->removeObjectData($post->post_type, $postId);
    }

    /**
     * @throws UserGroupTypeException
     */
    public function showMediaFile(array $formFields, WP_Post $post = null): array
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
