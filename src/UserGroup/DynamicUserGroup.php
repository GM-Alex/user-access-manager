<?php
/**
 * DynamicUserGroup.php
 *
 * The DynamicUserGroup class file.
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

namespace UserAccessManager\UserGroup;

use Exception;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class DynamicUserGroup
 *
 * @package UserAccessManager\UserGroup
 */
class DynamicUserGroup extends AbstractUserGroup
{
    const USER_TYPE = 'user';
    const ROLE_TYPE = 'role';
    const NOT_LOGGED_IN_USER_ID = 0;

    /**
     * @var string
     */
    protected $type = null;

    /**
     * DynamicUserGroup constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param Database $database
     * @param MainConfig $config
     * @param Util $util
     * @param ObjectHandler $objectHandler
     * @param AssignmentInformationFactory $assignmentInformationFactory
     * @param string $type
     * @param int|string $id
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
        string $type,
        $id
    ) {
        $this->type = $type;

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

        if ($type !== self::USER_TYPE && $type !== self::ROLE_TYPE) {
            throw new UserGroupTypeException('Invalid dynamic group type.');
        }
    }

    /**
     * Returns the dynamic user group id.
     * @return string
     */
    public function getId(): string
    {
        return $this->type . '|' . $this->id;
    }

    /**
     * Returns the dynamic group name.
     * @return string
     */
    public function getName(): string
    {
        if ($this->name === null) {
            $this->name = '';

            if ($this->type === self::USER_TYPE && (int) $this->id === self::NOT_LOGGED_IN_USER_ID) {
                $this->name = TXT_UAM_ADD_DYNAMIC_NOT_LOGGED_IN_USERS;
            } elseif ($this->type === self::USER_TYPE) {
                $userData = $this->wordpress->getUserData($this->id);
                $this->name = TXT_UAM_USER . ": {$userData->display_name} ($userData->user_login)";
            } elseif ($this->type === self::ROLE_TYPE) {
                $roles = $this->wordpress->getRoles()->roles;
                $this->name = TXT_UAM_ROLE . ': ';
                $this->name .= (isset($roles[$this->id]['name']) === true) ? $roles[$this->id]['name'] : $this->id;
            }
        }

        return $this->name;
    }

    /**
     * Checks if the user group is assigned to a user.
     * @param string $objectType
     * @param int|string $objectId
     * @param null $fromDate
     * @param null $toDate
     * @return bool
     * @throws UserGroupAssignmentException
     * @throws Exception
     */
    public function addObject(string $objectType, $objectId, $fromDate = null, $toDate = null): bool
    {
        if ($this->objectHandler->getGeneralObjectType($objectType) === ObjectHandler::GENERAL_USER_OBJECT_TYPE) {
            throw new UserGroupAssignmentException('Dynamic user groups can\'t be assigned to user.');
        }

        return parent::addObject($objectType, $objectId, $fromDate, $toDate);
    }
}
