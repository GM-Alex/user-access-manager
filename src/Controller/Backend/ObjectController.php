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
use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\ObjectMembership\MissingObjectMembershipHandlerException;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\User\UserHandler;
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
     * @var Cache
     */
    protected $cache;

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
     * @var AccessHandler
     */
    protected $accessHandler;

    /**
     * @var UserGroupFactory
     */
    protected $userGroupFactory;

    /**
     * @var null|string
     */
    protected $groupsFromName = null;

    /**
     * @var null|string
     */
    protected $objectType = null;

    /**
     * @var null|string
     */
    protected $objectId = null;

    /**
     * @var  AbstractUserGroup[]
     */
    protected $objectUserGroups = [];

    /**
     * @var int
     */
    protected $userGroupDiff = 0;

    /**
     * ObjectController constructor.
     *
     * @param Php              $php
     * @param Wordpress        $wordpress
     * @param WordpressConfig  $wordpressConfig
     * @param MainConfig       $mainConfig
     * @param Database         $database
     * @param DateUtil         $dateUtil
     * @param Cache            $cache
     * @param ObjectHandler    $objectHandler
     * @param UserHandler      $userHandler
     * @param AccessHandler    $accessHandler
     * @param UserGroupFactory $userGroupFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        MainConfig $mainConfig,
        Database $database,
        DateUtil $dateUtil,
        Cache $cache,
        ObjectHandler $objectHandler,
        UserHandler $userHandler,
        AccessHandler $accessHandler,
        UserGroupFactory $userGroupFactory
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
        $this->mainConfig = $mainConfig;
        $this->database = $database;
        $this->cache = $cache;
        $this->dateUtil = $dateUtil;
        $this->objectHandler = $objectHandler;
        $this->userHandler = $userHandler;
        $this->accessHandler = $accessHandler;
        $this->userGroupFactory = $userGroupFactory;
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
        $this->objectType = $objectType;
        $this->objectId = $objectId;
        $this->userGroupDiff = 0;

        if ($objectUserGroups === null && $objectId !== null) {
            $objectUserGroups = $this->accessHandler->getFilteredUserGroupsForObject($objectType, $objectId, true);
            $fullObjectUserGroups = $this->accessHandler->getUserGroupsForObject($objectType, $objectId, true);
            $this->userGroupDiff = count($fullObjectUserGroups) - count($objectUserGroups);
        }

        $this->objectUserGroups = (array)$objectUserGroups;
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
     * @return  AbstractUserGroup[]
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
     * @return  AbstractUserGroup[]
     */
    public function getUserGroups()
    {
        return $this->accessHandler->getFullUserGroups();
    }

    /**
     * Returns the filtered user groups.
     *
     * @return  AbstractUserGroup[]
     */
    public function getFilteredUserGroups()
    {
        return $this->accessHandler->getFilteredUserGroups();
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
        if ($this->objectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE
            && $this->objectId !== null
        ) {
            return $this->userHandler->userIsAdmin($this->objectId);
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
        $objectType = $this->getObjectType();
        $objectId = $this->getObjectId();
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
        $postId = $this->getRequestParameter('post', $this->getRequestParameter('attachment_id'));

        if ($postId !== null) {
            $this->dieOnNoAccess(ObjectHandler::GENERAL_POST_OBJECT_TYPE, $postId);
        }

        $tagId = $this->getRequestParameter('tag_ID');

        if ($tagId !== null) {
            $this->dieOnNoAccess(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $tagId);
        }
    }

    /**
     * @param array  $data
     * @param string $name
     *
     * @return null|string
     */
    private function getDateParameter(array $data, $name)
    {
        return (isset($data[$name]) === true && (string)$data[$name] !== '') ? (string)$data[$name] : null;
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

        $filteredUserGroupsForObject = $this->accessHandler->getFilteredUserGroupsForObject(
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
     * Updates the user groups for the given object.
     *
     * @param  AbstractUserGroup[] $filteredUserGroups
     * @param string              $objectType
     * @param string              $objectId
     * @param array               $addUserGroups
     * @param array               $removeUserGroups
     */
    private function setUserGroups(
        array $filteredUserGroups,
        $objectType,
        $objectId,
        array $addUserGroups,
        array $removeUserGroups
    ) {
        foreach ($filteredUserGroups as $groupId => $userGroup) {
            if (isset($removeUserGroups[$groupId]) === true) {
                $userGroup->removeObject($objectType, $objectId);
            }

            if (isset($addUserGroups[$groupId]['id']) === true
                && (int)$addUserGroups[$groupId]['id'] === (int)$groupId
            ) {
                $userGroup->addObject(
                    $objectType,
                    $objectId,
                    $this->getDateParameter($addUserGroups[$groupId], 'fromDate'),
                    $this->getDateParameter($addUserGroups[$groupId], 'toDate')
                );
            }
        }
    }

    /**
     * Sets the dynamic user groups for the given object.
     *
     * @param string $objectType
     * @param string $objectId
     */
    private function setDynamicGroups($objectType, $objectId)
    {
        $addDynamicUserGroups = $this->getRequestParameter(self::DEFAULT_DYNAMIC_GROUPS_FORM_NAME, []);

        foreach ($addDynamicUserGroups as $dynamicUserGroupKey => $addDynamicUserGroup) {
            $dynamicUserGroupData = explode('|', $dynamicUserGroupKey);

            if (count($dynamicUserGroupData) === 2
                && $addDynamicUserGroup['id'] === $dynamicUserGroupKey
            ) {
                $dynamicUserGroup = $this->userGroupFactory->createDynamicUserGroup(
                    $dynamicUserGroupData[0],
                    $dynamicUserGroupData[1]
                );

                $dynamicUserGroup->addObject(
                    $objectType,
                    $objectId,
                    $this->getDateParameter($addDynamicUserGroup, 'fromDate'),
                    $this->getDateParameter($addDynamicUserGroup, 'toDate')
                );
            }
        }
    }

    /**
     * Sets the default user groups for the given object.
     *
     * @param  AbstractUserGroup[] $filteredUserGroups
     * @param string              $objectType
     * @param string              $objectId
     */
    private function setDefaultGroups(array $filteredUserGroups, $objectType, $objectId)
    {
        /**
         * @var UserGroup[] $userGroupsToCheck
         */
        $userGroupsToCheck = array_diff_key($this->getUserGroups(), $filteredUserGroups);

        foreach ($userGroupsToCheck as $userGroupToCheck) {
            if ($userGroupToCheck->isDefaultGroupForObjectType($objectType, $fromTime, $toTime) === true) {
                $userGroupToCheck->addObject(
                    $objectType,
                    $objectId,
                    $this->dateUtil->getDateFromTime($fromTime),
                    $this->dateUtil->getDateFromTime($toTime)
                );
            }
        }
    }

    /**
     * Saves the object data to the database.
     *
     * @param string $objectType    The object type.
     * @param string $objectId      The id of the object.
     * @param array  $addUserGroups The new user groups for the object.
     */
    public function saveObjectData($objectType, $objectId, array $addUserGroups = null)
    {
        $isUpdateForm = (bool)$this->getRequestParameter(self::UPDATE_GROUPS_FORM_NAME, false) === true
            || $this->getRequestParameter('uam_bulk_type') !== null;

        $hasRights = $this->checkUserAccess() === true || $this->mainConfig->authorsCanAddPostsToGroups() === true;

        if ($isUpdateForm === true && $hasRights === true) {
            $filteredUserGroups = $this->accessHandler->getFilteredUserGroups();
            $this->getAddRemoveGroups($objectType, $objectId, $addUserGroups, $removeUserGroups);
            $this->setUserGroups($filteredUserGroups, $objectType, $objectId, $addUserGroups, $removeUserGroups);

            if ($this->checkUserAccess() === true) {
                $this->setDynamicGroups($objectType, $objectId);
            } else {
                $this->setDefaultGroups($filteredUserGroups, $objectType, $objectId);
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
        if ($this->objectType !== null) {
            $generalObjectType = $this->objectHandler->getGeneralObjectType($this->objectType);

            return ($this->objectId === null
                || ($generalObjectType === ObjectHandler::GENERAL_POST_OBJECT_TYPE &&
                    $this->getRequestParameter('action') !== 'edit')
            );
        }

        return false;
    }
}
