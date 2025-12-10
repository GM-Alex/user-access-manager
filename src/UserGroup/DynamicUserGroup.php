<?php

declare(strict_types=1);

namespace UserAccessManager\UserGroup;

use Exception;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

class DynamicUserGroup extends AbstractUserGroup
{
    public const USER_TYPE = 'user';
    public const ROLE_TYPE = 'role';
    public const NOT_LOGGED_IN_USER_ID = 0;

    /**
     * @throws UserGroupTypeException
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Database $database,
        MainConfig $config,
        Util $util,
        ObjectHandler $objectHandler,
        AssignmentInformationFactory $assignmentInformationFactory,
        protected ?string $type,
        int|string $id
    ) {
        parent::__construct(
            $php,
            $wordpress,
            $database,
            $config,
            $util,
            $objectHandler,
            $assignmentInformationFactory,
            $id
        );

        if ($this->type !== self::USER_TYPE && $this->type !== self::ROLE_TYPE) {
            throw new UserGroupTypeException('Invalid dynamic group type.');
        }
    }

    public function getId(): string
    {
        return $this->type . '|' . $this->id;
    }

    public function getName(): string
    {
        if ($this->name === null) {
            $this->name = '';

            if ($this->type === self::USER_TYPE && (int) $this->id === self::NOT_LOGGED_IN_USER_ID) {
                $this->name = TXT_UAM_ADD_DYNAMIC_NOT_LOGGED_IN_USERS;
            } elseif ($this->type === self::USER_TYPE) {
                $userData = $this->wordpress->getUserData($this->id);
                $userName = $userData !== false ? "$userData->display_name ($userData->user_login)" : '';
                $this->name = TXT_UAM_USER . ": $userName";
            } elseif ($this->type === self::ROLE_TYPE) {
                $roles = $this->wordpress->getRoles()->roles;
                $this->name = TXT_UAM_ROLE . ': ';
                $this->name .= (isset($roles[$this->id]['name']) === true) ? $roles[$this->id]['name'] : $this->id;
            }
        }

        return $this->name;
    }

    /**
     * @throws UserGroupAssignmentException
     * @throws Exception
     */
    public function addObject(string $objectType, int|string|null $objectId, $fromDate = null, $toDate = null): bool
    {
        if ($this->objectHandler->getGeneralObjectType($objectType) === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
            throw new UserGroupAssignmentException('Dynamic user groups can\'t be assigned to user.');
        }

        return parent::addObject($objectType, $objectId, $fromDate, $toDate);
    }
}
