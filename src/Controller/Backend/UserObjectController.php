<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;

class UserObjectController extends ObjectController
{
    public function addUserColumnsHeader(array $defaults): array
    {
        $defaults[self::COLUMN_NAME] = TXT_UAM_COLUMN_USER_GROUPS;
        return $defaults;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function addUserColumn(?string $return, string $columnName, int|string|null $id): ?string
    {
        if ($columnName === self::COLUMN_NAME) {
            $this->setObjectInformation(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $id);
            $return .= $this->getIncludeContents('UserColumn.php');
        }

        return $return;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function showUserProfile(): void
    {
        $userId = $this->getRequestParameter('user_id');
        $this->setObjectInformation(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $userId);

        echo $this->getIncludeContents('UserProfileEditForm.php');
    }

    /**
     * @throws UserGroupTypeException
     */
    public function saveUserData(int|string|null $userId): void
    {
        $this->saveObjectData(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $userId);
    }

    public function removeUserData(int|string|null $userId): void
    {
        $this->removeObjectData(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $userId);
    }
}
