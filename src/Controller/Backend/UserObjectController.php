<?php
/**
 * UserObjectController.php
 *
 * The UserObjectController class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller\Backend;

use UserAccessManager\Object\ObjectHandler;

/**
 * Class UserObjectController
 *
 * @package UserAccessManager\Controller\Backend
 */
class UserObjectController extends ObjectController
{
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
        $this->setObjectInformation(ObjectHandler::GENERAL_USER_OBJECT_TYPE, $userId);

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
}
