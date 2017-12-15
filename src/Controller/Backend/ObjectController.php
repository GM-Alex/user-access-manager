<?php
/**
 * ObjectController.php
 *
 * The ObjectController class file.
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

use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\ObjectMembership\MissingObjectMembershipHandlerException;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\DynamicUserGroup;
use UserAccessManager\UserGroup\UserGroupAssignmentException;
use UserAccessManager\UserGroup\UserGroupAssignmentHandler;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\Util\DateUtil;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class ObjectController
 *
 * @package UserAccessManager\Controller
 */
class ObjectController extends Controller
{
    const COLUMN_NAME = 'uam_access';
    const BULK_REMOVE = 'remove';
    const DEFAULT_GROUPS_FORM_NAME = 'uam_user_groups';
    const DEFAULT_DYNAMIC_GROUPS_FORM_NAME = 'uam_dynamic_user_groups';
    const UPDATE_GROUPS_FORM_NAME = 'uam_update_groups';

    /**
     * @var MainConfig
     */
    protected $mainConfig;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var DateUtil
     */
    protected $dateUtil;

    /**
     * @var ObjectHandler
     */
    protected $objectHandler;

    /**
     * @var UserHandler
     */
    protected $userHandler;

    /**
     * @var UserGroupHandler
     */
    protected $userGroupHandler;

    /**
     * @var AccessHandler
     */
    protected $accessHandler;

    /**
     * @var UserGroupAssignmentHandler
     */
    protected $userGroupAssignmentHandler;

    /**
     * @var ObjectInformation
     */
    protected $objectInformation;

    /**
     * @var null|string
     */
    protected $groupsFromName = null;

    /**
     * ObjectController constructor.
     *
     * @param Php                        $php
     * @param Wordpress                  $wordpress
     * @param WordpressConfig            $wordpressConfig
     * @param MainConfig                 $mainConfig
     * @param Database                   $database
     * @param DateUtil                   $dateUtil
     * @param ObjectHandler              $objectHandler
     * @param UserHandler                $userHandler
     * @param UserGroupHandler           $userGroupHandler
     * @param UserGroupAssignmentHandler $userGroupAssignmentHandler
     * @param AccessHandler              $accessHandler
     * @param ObjectInformationFactory   $objectInformationFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        Database $database,
        DateUtil $dateUtil,
        ObjectHandler $objectHandler,
        UserHandler $userHandler,
        UserGroupHandler $userGroupHandler,
        UserGroupAssignmentHandler $userGroupAssignmentHandler,
        AccessHandler $accessHandler,
        ObjectInformationFactory $objectInformationFactory
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
        $this->mainConfig = $mainConfig;
        $this->database = $database;
        $this->dateUtil = $dateUtil;
        $this->objectHandler = $objectHandler;
        $this->userHandler = $userHandler;
        $this->userGroupHandler = $userGroupHandler;
        $this->userGroupAssignmentHandler = $userGroupAssignmentHandler;
        $this->accessHandler = $accessHandler;
        $this->objectInformation = $objectInformationFactory->createObjectInformation();
    }

    /**
     * Sets the current object type, the object id and the user groups.
     *
     * @param string $objectType
     * @param string $objectId
     * @param array  $objectUserGroups
     */
    protected function setObjectInformation($objectType, $objectId, array $objectUserGroups = null)
    {
        $userGroupDiff = 0;

        if ($objectUserGroups === null && $objectId !== null) {
            $objectUserGroups = $this->userGroupHandler->getFilteredUserGroupsForObject($objectType, $objectId, true);
            $fullObjectUserGroups = $this->userGroupHandler->getUserGroupsForObject($objectType, $objectId, true);
            $userGroupDiff = count($fullObjectUserGroups) - count($objectUserGroups);
        }

        $this->objectInformation->setObjectType($objectType)
            ->setObjectId($objectId)
            ->setObjectUserGroups((array)$objectUserGroups)
            ->setUserGroupDiff($userGroupDiff);
    }

    /**
     * Returns the object information.
     *
     * @return ObjectInformation
     */
    public function getObjectInformation()
    {
        return $this->objectInformation;
    }

    /**
     * Returns the default groups form name.
     *
     * @return string
     */
    public function getGroupsFormName()
    {
        return ($this->groupsFromName !== null) ? (string)$this->groupsFromName : self::DEFAULT_GROUPS_FORM_NAME;
    }

    /**
     * Returns the filtered user groups.
     *
     * @return  AbstractUserGroup[]
     */
    public function getFilteredUserGroups()
    {
        return $this->userGroupHandler->getFilteredUserGroups();
    }

    /**
     * Sorts the user groups.
     *
     * @param array $userGroups
     */
    public function sortUserGroups(array &$userGroups)
    {
        uasort(
            $userGroups,
            function (
                AbstractUserGroup $userGroupOne,
                AbstractUserGroup $userGroupTwo
            ) {
                $notLoggedInUserGroupId = DynamicUserGroup::USER_TYPE.'|'.DynamicUserGroup::NOT_LOGGED_IN_USER_ID;

                if ($userGroupOne->getId() === $notLoggedInUserGroupId) {
                    return 1;
                } elseif ($userGroupTwo->getId() === $notLoggedInUserGroupId) {
                    return 0;
                }

                return strnatcasecmp($userGroupOne->getName(), $userGroupTwo->getName());
            }
        );
    }

    /**
     * Returns the date util.
     *
     * @return DateUtil
     */
    public function getDateUtil()
    {
        return $this->dateUtil;
    }

    /**
     * Checks if the current user is an admin.
     *
     * @return bool
     */
    public function isCurrentUserAdmin()
    {
        if ($this->objectInformation->getObjectType() === ObjectHandler::GENERAL_USER_OBJECT_TYPE
            && $this->objectInformation->getObjectId() !== null
        ) {
            return $this->userHandler->userIsAdmin($this->objectInformation->getObjectId());
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
     * Checks the user access.
     *
     * @return bool
     */
    public function checkUserAccess()
    {
        return $this->userHandler->checkUserAccess(UserHandler::MANAGE_USER_GROUPS_CAPABILITY);
    }

    /**
     * Returns the recursive object membership.
     *
     * @param $userGroup
     *
     * @return array
     */
    public function getRecursiveMembership(AbstractUserGroup $userGroup)
    {
        $recursiveMembership = [];
        $objectType = $this->objectInformation->getObjectType();
        $objectId = $this->objectInformation->getObjectId();
        $recursiveMembershipForObject = $userGroup->getRecursiveMembershipForObject($objectType, $objectId);

        /**
         * @var AssignmentInformation[] $assignmentInformation
         */
        foreach ($recursiveMembershipForObject as $recursiveType => $assignmentInformation) {
            foreach ($assignmentInformation as $objectId => $information) {
                try {
                    $membershipHandler = $this->objectHandler->getObjectMembershipHandler($information->getType());
                    $typeName = $membershipHandler->getGeneralObjectType();
                    $objectName = $membershipHandler->getObjectName($objectId, $typeName);
                    $recursiveMembership[$typeName][$objectId] = $objectName;
                } catch (MissingObjectMembershipHandlerException $exception) {
                    // Do nothing
                }
            }
        }

        return $recursiveMembership;
    }

    /**
     * Checks the access and dies if the user has no access.
     *
     * @param string $objectType
     * @param string $objectId
     */
    private function dieOnNoAccess($objectType, $objectId)
    {
        if ($this->accessHandler->checkObjectAccess($objectType, $objectId) === false) {
            $this->wordpress->wpDie(TXT_UAM_NO_RIGHTS_MESSAGE, TXT_UAM_NO_RIGHTS_TITLE, ['response' => 403]);
        }
    }

    /**
     * Shows the error if the user has no rights to edit the content.
     */
    public function checkRightsToEditContent()
    {
        $postIdParameter = $this->getRequestParameter('post', $this->getRequestParameter('attachment_id'));

        if ($postIdParameter !== null) {
            $postIds = is_array($postIdParameter) === false ? [$postIdParameter] : $postIdParameter;

            foreach ($postIds as $postId) {
                $this->dieOnNoAccess(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $postId);
            }
        }

        $tagId = $this->getRequestParameter('tag_ID');

        if ($tagId !== null) {
            $this->dieOnNoAccess(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $tagId);
        }
    }

    /**
     * Returns the user groups by reference which should be add and removed from the object.
     *
     * @param string     $objectType
     * @param string     $objectId
     * @param array|null $addUserGroups
     * @param array      $removeUserGroups
     */
    private function getAddRemoveGroups($objectType, $objectId, &$addUserGroups, &$removeUserGroups)
    {
        if ($addUserGroups === null) {
            $addUserGroups = (array)$this->getRequestParameter(self::DEFAULT_GROUPS_FORM_NAME, []);
        }

        $filteredUserGroupsForObject = $this->userGroupHandler->getFilteredUserGroupsForObject(
            $objectType,
            $objectId
        );
        $removeUserGroups = array_flip(array_keys($filteredUserGroupsForObject));
        $bulkType = $this->getRequestParameter('uam_bulk_type');

        if ($bulkType === self::BULK_REMOVE) {
            $removeUserGroups = $addUserGroups;
            $addUserGroups = [];
        }
    }

    /**
     * Saves the object data to the database.
     *
     * @param string $objectType    The object type.
     * @param string $objectId      The id of the object.
     * @param array  $addUserGroups The new user groups for the object.
     * @param bool   $force         If true we force the assignment.
     */
    public function saveObjectData($objectType, $objectId, array $addUserGroups = null, $force = false)
    {
        $isUpdateForm = (bool)$this->getRequestParameter(self::UPDATE_GROUPS_FORM_NAME, false) === true
            || $this->getRequestParameter('uam_bulk_type') !== null;

        $hasRights = $this->checkUserAccess() === true || $this->mainConfig->authorsCanAddPostsToGroups() === true;

        if ($isUpdateForm === true && $hasRights === true || $force === true) {
            $this->getAddRemoveGroups($objectType, $objectId, $addUserGroups, $removeUserGroups);

            try {
                $this->userGroupAssignmentHandler->assignObjectToUserGroups(
                    $objectType,
                    $objectId,
                    $addUserGroups,
                    $removeUserGroups,
                    $this->getRequestParameter(self::DEFAULT_DYNAMIC_GROUPS_FORM_NAME, [])
                );
            } catch (UserGroupAssignmentException $exception) {
                $this->addErrorMessage(sprintf(TXT_UAM_ERROR, $exception->getMessage()));
            }
        }
    }

    /**
     * Removes the object data.
     *
     * @param string $objectType The object type.
     * @param int    $id         The object id.
     */
    public function removeObjectData($objectType, $id)
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
    public function showGroupSelectionForm(
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
    public function getGroupColumn($objectType, $objectId)
    {
        $this->setObjectInformation($objectType, $objectId);
        return $this->getIncludeContents('ObjectColumn.php');
    }

    /**
     * Checks if the current object is a new object.
     *
     * @return bool
     */
    public function isNewObject()
    {
        $objectType = $this->objectInformation->getObjectType();

        if ($objectType !== null) {
            $generalObjectType = $this->objectHandler->getGeneralObjectType($objectType);

            return ($this->objectInformation->getObjectId() === null
                || ($generalObjectType === ObjectHandler::GENERAL_POST_OBJECT_TYPE &&
                    $this->getRequestParameter('action') !== 'edit')
            );
        }

        return false;
    }
}
