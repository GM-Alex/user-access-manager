<?php
/**
 * UserGroupFactory.php
 *
 * The UserGroupFactory class file.
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

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class UserGroupFactory
 *
 * @package UserAccessManager\UserGroup
 */
class UserGroupFactory
{
    /**
     * @var Php
     */
    private $php;

    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var MainConfig
     */
    private $config;

    /**
     * @var Util
     */
    private $util;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var AssignmentInformationFactory
     */
    private $assignmentInformationFactory;

    /**
     * UserGroupFactory constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param Database $database
     * @param MainConfig $config
     * @param Util $util
     * @param ObjectHandler $objectHandler
     * @param AssignmentInformationFactory $assignmentInformationFactory
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Database $database,
        MainConfig $config,
        Util $util,
        ObjectHandler $objectHandler,
        AssignmentInformationFactory $assignmentInformationFactory
    ) {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->database = $database;
        $this->config = $config;
        $this->util = $util;
        $this->objectHandler = $objectHandler;
        $this->assignmentInformationFactory = $assignmentInformationFactory;
    }

    /**
     * Creates a new user group object.
     * @param null|string $id
     * @return UserGroup
     * @throws UserGroupTypeException
     */
    public function createUserGroup($id = null): UserGroup
    {
        return new UserGroup(
            $this->php,
            $this->wordpress,
            $this->database,
            $this->config,
            $this->util,
            $this->objectHandler,
            $this->assignmentInformationFactory,
            $id
        );
    }

    /**
     * Creates a new dynamic user group object.
     * @param string $type
     * @param int|string $id
     * @return DynamicUserGroup
     * @throws UserGroupTypeException
     */
    public function createDynamicUserGroup(string $type, $id): DynamicUserGroup
    {
        return new DynamicUserGroup(
            $this->php,
            $this->wordpress,
            $this->database,
            $this->config,
            $this->util,
            $this->objectHandler,
            $this->assignmentInformationFactory,
            $type,
            $id
        );
    }
}
