<?php /** @noinspection PhpCastIsUnnecessaryInspection */
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

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

use Exception;
use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Controller;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\ObjectMembership\MissingObjectMembershipHandlerException;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\DynamicUserGroup;
use UserAccessManager\UserGroup\UserGroupAssignmentException;
use UserAccessManager\UserGroup\UserGroupAssignmentHandler;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
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
    public const COLUMN_NAME = 'uam_access';
    public const BULK_ADD = 'add';
    public const BULK_REMOVE = 'remove';
    public const BULK_OVERWRITE = 'overwrite';
    public const DEFAULT_GROUPS_FORM_NAME = 'uam_user_groups';
    public const DEFAULT_DYNAMIC_GROUPS_FORM_NAME = 'uam_dynamic_user_groups';
    public const UPDATE_GROUPS_FORM_NAME = 'uam_update_groups';

    protected ObjectInformation $objectInformation;

    /**
     * @var null|string
     */
    protected ?string $groupsFromName = null;

    public function __construct(
        Php $php,
        Wordpress $wordpress,
        WordpressConfig $wordpressConfig,
        protected MainConfig $mainConfig,
        protected Database $database,
        protected DateUtil $dateUtil,
        protected ObjectHandler $objectHandler,
        protected UserHandler $userHandler,
        protected UserGroupHandler $userGroupHandler,
        protected UserGroupAssignmentHandler $userGroupAssignmentHandler,
        protected AccessHandler $accessHandler,
        ObjectInformationFactory $objectInformationFactory
    ) {
        parent::__construct($php, $wordpress, $wordpressConfig);
        $this->objectInformation = $objectInformationFactory->createObjectInformation();
    }

    /**
     * @throws UserGroupTypeException
     */
    protected function setObjectInformation(
        string $objectType,
        int|string|null $objectId,
        ?array $objectUserGroups = null
    ): void {
        $userGroupDiff = 0;

        if ($objectUserGroups === null && $objectId !== null) {
            $objectUserGroups = $this->userGroupHandler->getFilteredUserGroupsForObject($objectType, $objectId, true);
            $fullObjectUserGroups = $this->userGroupHandler->getUserGroupsForObject($objectType, $objectId, true);
            $userGroupDiff = count((array) $fullObjectUserGroups) - count((array) $objectUserGroups);
        }

        $this->objectInformation->setObjectType($objectType)
            ->setObjectId($objectId)
            ->setObjectUserGroups((array) $objectUserGroups)
            ->setUserGroupDiff($userGroupDiff);
    }

    public function getObjectInformation(): ObjectInformation
    {
        return $this->objectInformation;
    }

    public function getGroupsFormName(): string
    {
        return ($this->groupsFromName !== null) ? (string) $this->groupsFromName : self::DEFAULT_GROUPS_FORM_NAME;
    }

    /**
     * @return AbstractUserGroup[]
     * @throws UserGroupTypeException
     */
    public function getFilteredUserGroups(): array
    {
        return $this->userGroupHandler->getFilteredUserGroups();
    }

    public function sortUserGroups(array &$userGroups): void
    {
        uasort(
            $userGroups,
            function (
                AbstractUserGroup $userGroupOne,
                AbstractUserGroup $userGroupTwo
            ) {
                $notLoggedInUserGroupId = DynamicUserGroup::USER_TYPE . '|' . DynamicUserGroup::NOT_LOGGED_IN_USER_ID;

                if ($userGroupOne->getId() === $notLoggedInUserGroupId) {
                    return 1;
                } elseif ($userGroupTwo->getId() === $notLoggedInUserGroupId) {
                    return -1;
                }

                return strnatcasecmp($userGroupOne->getName(), $userGroupTwo->getName());
            }
        );
    }

    public function getDateUtil(): DateUtil
    {
        return $this->dateUtil;
    }

    public function isCurrentUserAdmin(): bool
    {
        if ($this->objectInformation->getObjectType() === ObjectHandler::GENERAL_USER_OBJECT_TYPE
            && $this->objectInformation->getObjectId() !== null
        ) {
            return $this->userHandler->userIsAdmin($this->objectInformation->getObjectId());
        }

        return false;
    }

    public function getRoleNames(): array
    {
        $roles = $this->wordpress->getRoles();
        return $roles->role_names;
    }

    public function checkUserAccess(): bool
    {
        return $this->userHandler->checkUserAccess(UserHandler::MANAGE_USER_GROUPS_CAPABILITY);
    }

    /**
     * @throws Exception
     */
    public function getRecursiveMembership(AbstractUserGroup $userGroup): array
    {
        $recursiveMembership = [];
        $objectType = $this->objectInformation->getObjectType();
        $objectId = $this->objectInformation->getObjectId();
        $recursiveMembershipForObject = $userGroup->getRecursiveMembershipForObject($objectType, $objectId);

        /**
         * @var AssignmentInformation[] $assignmentInformation
         */
        foreach ($recursiveMembershipForObject as $assignmentInformation) {
            foreach ($assignmentInformation as $objectId => $information) {
                try {
                    $membershipHandler = $this->objectHandler->getObjectMembershipHandler($information->getType());
                    $typeName = $membershipHandler->getGeneralObjectType();
                    $objectName = $membershipHandler->getObjectName($objectId, $typeName);
                    $recursiveMembership[$typeName][$objectId] = $objectName;
                } catch (MissingObjectMembershipHandlerException) {}
            }
        }

        return $recursiveMembership;
    }

    /**
     * @throws UserGroupTypeException
     */
    private function dieOnNoAccess(string $objectType, int|string $objectId): void
    {
        if ($this->accessHandler->checkObjectAccess($objectType, $objectId) === false) {
            $this->wordpress->wpDie(TXT_UAM_NO_RIGHTS_MESSAGE, TXT_UAM_NO_RIGHTS_TITLE, ['response' => 403]);
        }
    }

    /**
     * @throws UserGroupTypeException
     */
    public function checkRightsToEditContent(): void
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
     * @throws UserGroupTypeException
     */
    private function getAddRemoveGroups(
        string $objectType,
        int|string $objectId,
        ?array &$addUserGroups = [],
        ?array &$removeUserGroups = []
    ): void {
        $groupsToChange = (array) $this->getRequestParameter(self::DEFAULT_GROUPS_FORM_NAME, []);
        $filteredUserGroupsForObject = $this->userGroupHandler->getFilteredUserGroupsForObject(
            $objectType,
            $objectId
        );

        $addUserGroups = $addUserGroups ?? $groupsToChange;
        $removeUserGroups = array_flip(array_keys($filteredUserGroupsForObject));
        $bulkType = $this->getRequestParameter('uam_bulk_type');

        if ($bulkType === self::BULK_ADD) {
            $addUserGroups = $groupsToChange;
            $removeUserGroups = [];
        } elseif ($bulkType === self::BULK_REMOVE) {
            $addUserGroups = [];
            $removeUserGroups = array_filter(
                $groupsToChange,
                function (array $group) {
                    return isset($group['id']);
                }
            );
        }
    }

    /**
     * @throws UserGroupTypeException
     */
    public function saveObjectData(
        string $objectType,
        int|string $objectId,
        ?array $addUserGroups = null,
        bool $force = false
    ): void {
        $isUpdateForm = (bool) $this->getRequestParameter(self::UPDATE_GROUPS_FORM_NAME, false) === true
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

    public function removeObjectData(string $objectType, int|string $id): void
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
     * @throws UserGroupTypeException
     */
    public function showGroupSelectionForm(
        string     $objectType,
        int|string $objectId,
                   $formName = null,
        array      $objectUserGroups = null
    ): string {
        $this->setObjectInformation($objectType, $objectId, $objectUserGroups);

        $this->groupsFromName = $formName;
        $formContent = $this->getIncludeContents('GroupSelectionForm.php');
        $this->groupsFromName = null;

        return $formContent;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function getGroupColumn(string $objectType, int|string $objectId): string
    {
        $this->setObjectInformation($objectType, $objectId);
        return $this->getIncludeContents('ObjectColumn.php');
    }

    /**
     * @throws Exception
     */
    public function isNewObject(): bool
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
