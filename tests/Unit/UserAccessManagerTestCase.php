<?php
/**
 * UserAccessManagerTestCase.php
 *
 * The UserAccessManagerTestCase class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use stdClass;
use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Cache\CacheProviderFactory;
use UserAccessManager\Cache\FileSystemCacheProvider;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigFactory;
use UserAccessManager\Config\ConfigParameter;
use UserAccessManager\Config\ConfigParameterFactory;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Backend\ObjectInformationFactory;
use UserAccessManager\Controller\ControllerFactory;
use UserAccessManager\Database\Database;
use UserAccessManager\File\FileHandler;
use UserAccessManager\File\FileObjectFactory;
use UserAccessManager\File\FileProtectionFactory;
use UserAccessManager\Form\FormFactory;
use UserAccessManager\Form\FormHelper;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\ObjectMembership\ObjectMembershipHandlerFactory;
use UserAccessManager\Setup\Database\DatabaseHandler;
use UserAccessManager\Setup\Database\DatabaseObjectFactory;
use UserAccessManager\Setup\SetupHandler;
use UserAccessManager\Setup\Update\UpdateFactory;
use UserAccessManager\User\UserHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\UserGroup\DynamicUserGroup;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\UserGroup\UserGroupAssignmentHandler;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\UserGroup\UserGroupHandler;
use UserAccessManager\Util\DateUtil;
use UserAccessManager\Util\Util;
use UserAccessManager\Widget\WidgetFactory;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;
use UserAccessManager\Wrapper\WordpressCli;

/**
 * Class UserAccessManagerTestCase
 */
abstract class UserAccessManagerTestCase extends TestCase
{
    protected function setUp(): void
    {
        unset($_GET);
        unset($_POST);
        unset($_SESSION);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        unset($_GET);
        unset($_POST);
        unset($_SESSION);
        parent::tearDown();
    }

    /**
     * Calls a private or protected object method.
      * @param object $object
     * @param string $methodName
     * @param array $arguments
      * @return mixed
     * @throws ReflectionException
     */
    public static function callMethod(object $object, string $methodName, array $arguments = []): mixed
    {
        $class = new ReflectionClass($object);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $arguments);
    }

    /**
     * Sets a private property
      * @param object $object
     * @param string $valueName
     * @param mixed $value
     * @throws ReflectionException
     */
    public static function setValue(object $object, string $valueName, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($valueName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    /**
     * @return MockObject|Php
     */
    protected function getPhp(): MockObject|Php
    {
        return $this->createMock(Php::class);
    }

    /**
     * @return MockObject|Wordpress
     */
    protected function getWordpress(): MockObject|Wordpress
    {
        return $this->createMock(Wordpress::class);
    }

    /**
     * @return MockObject|WordpressCli
     */
    protected function getWordpressCli(): MockObject|WordpressCli
    {
        return $this->createMock(WordpressCli::class);
    }

    /**
     * @return MockObject|Config
     */
    protected function getConfig(): Config|MockObject
    {
        return $this->createMock(Config::class);
    }

    /**
     * @return MockObject|WordpressConfig
     */
    protected function getWordpressConfig(): MockObject|WordpressConfig
    {
        return $this->createMock(WordpressConfig::class);
    }


    /**
     * @return MockObject|MainConfig
     */
    protected function getMainConfig(): MockObject|MainConfig
    {
        return $this->createMock(MainConfig::class);
    }

    /**
     * @return MockObject|Database
     */
    protected function getDatabase(): Database|MockObject
    {
        return $this->createMock(Database::class);
    }

    /**
     * @return MockObject|DatabaseHandler
     */
    protected function getDatabaseHandler(): DatabaseHandler|MockObject
    {
        return $this->createMock(DatabaseHandler::class);
    }

    /**
     * @return MockObject|DatabaseObjectFactory
     */
    protected function getDatabaseObjectFactory(): DatabaseObjectFactory|MockObject
    {
        return $this->createMock(DatabaseObjectFactory::class);
    }

    /**
     * @return MockObject|Util
     */
    protected function getUtil(): MockObject|Util
    {
        return $this->createMock(Util::class);
    }

    /**
     * @return MockObject|DateUtil
     */
    protected function getDateUtil(): MockObject|DateUtil
    {
        return $this->createMock(DateUtil::class);
    }

    /**
     * @return MockObject|Cache
     */
    protected function getCache(): Cache|MockObject
    {
        return $this->createMock(Cache::class);
    }

    /**
     * @return MockObject|CacheProviderFactory
     */
    protected function getCacheProviderFactory(): MockObject|CacheProviderFactory
    {
        return $this->createMock(CacheProviderFactory::class);
    }

    /**
     * @return MockObject|ConfigFactory
     */
    protected function getConfigFactory(): MockObject|ConfigFactory
    {
        return $this->createMock(ConfigFactory::class);
    }

    /**
     * @return MockObject|FileSystemCacheProvider
     */
    protected function getFileSystemCacheProvider(): MockObject|FileSystemCacheProvider
    {
        return $this->createMock(FileSystemCacheProvider::class);
    }

    /**
     * @return MockObject|ObjectHandler
     */
    protected function getObjectHandler(): ObjectHandler|MockObject
    {
        return $this->createMock(ObjectHandler::class);
    }

    /**
     * @return MockObject|ObjectMapHandler
     */
    protected function getObjectMapHandler(): MockObject|ObjectMapHandler
    {
        return $this->createMock(ObjectMapHandler::class);
    }

    /**
     * @return MockObject|AccessHandler
     */
    protected function getAccessHandler(): MockObject|AccessHandler
    {
        return $this->createMock(AccessHandler::class);
    }

    /**
     * @return MockObject|UserHandler
     */
    protected function getUserHandler(): UserHandler|MockObject
    {
        return $this->createMock(UserHandler::class);
    }

    /**
     * @return MockObject|UserGroupHandler
     */
    protected function getUserGroupHandler(): UserGroupHandler|MockObject
    {
        return $this->createMock(UserGroupHandler::class);
    }

    /**
     * @return MockObject|SetupHandler
     */
    protected function getSetupHandler(): MockObject|SetupHandler
    {
        return $this->createMock(SetupHandler::class);
    }

    /**
     * @return MockObject|ControllerFactory
     */
    protected function getControllerFactory(): ControllerFactory|MockObject
    {
        return $this->createMock(ControllerFactory::class);
    }

    /**
     * @return MockObject|WidgetFactory
     */
    protected function getWidgetFactory(): MockObject|WidgetFactory
    {
        return $this->createMock(WidgetFactory::class);
    }

    /**
     * @return MockObject|FileHandler
     */
    protected function getFileHandler(): MockObject|FileHandler
    {
        return $this->createMock(FileHandler::class);
    }

    /**
     * @return MockObject|FileObjectFactory
     */
    protected function getFileObjectFactory(): MockObject|FileObjectFactory
    {
        return $this->createMock(FileObjectFactory::class);
    }

    /**
     * @return MockObject|UserGroupFactory
     */
    protected function getUserGroupFactory(): MockObject|UserGroupFactory
    {
        return $this->createMock(UserGroupFactory::class);
    }

    /**
     * @return MockObject|UserGroupAssignmentHandler
     */
    protected function getUserGroupAssignmentHandler(): UserGroupAssignmentHandler|MockObject
    {
        return $this->createMock(UserGroupAssignmentHandler::class);
    }

    /**
     * @return MockObject|ConfigParameterFactory
     */
    protected function getConfigParameterFactory(): ConfigParameterFactory|MockObject
    {
        return $this->createMock(ConfigParameterFactory::class);
    }

    /**
     * @return MockObject|FileProtectionFactory
     */
    protected function getFileProtectionFactory(): FileProtectionFactory|MockObject
    {
        return $this->createMock(FileProtectionFactory::class);
    }

    /**
     * @return MockObject|FormFactory
     */
    protected function getFormFactory(): FormFactory|MockObject
    {
        return $this->createMock(FormFactory::class);
    }

    /**
     * @return MockObject|FormHelper
     */
    protected function getFormHelper(): FormHelper|MockObject
    {
        return $this->createMock(FormHelper::class);
    }

    /**
     * @return MockObject|UserAccessManager
     */
    protected function getUserAccessManager(): UserAccessManager|MockObject
    {
        return $this->createMock(UserAccessManager::class);
    }

    /**
     * @return MockObject|ObjectMembershipHandlerFactory
     */
    protected function getObjectMembershipHandlerFactory(): ObjectMembershipHandlerFactory|MockObject
    {
        return $this->createMock(ObjectMembershipHandlerFactory::class);
    }

    /**
     * @return MockObject|AssignmentInformationFactory
     */
    protected function getAssignmentInformationFactory(): MockObject|AssignmentInformationFactory
    {
        return $this->createMock(AssignmentInformationFactory::class);
    }

    /**
     * @return MockObject|UpdateFactory
     */
    protected function getUpdateFactory(): UpdateFactory|MockObject
    {
        return $this->createMock(UpdateFactory::class);
    }

    /**
     * @return MockObject|ObjectInformationFactory
     */
    protected function getObjectInformationFactory(): ObjectInformationFactory|MockObject
    {
        return $this->createMock(ObjectInformationFactory::class);
    }

    /**
     * @return MockObject|AssignmentInformationFactory
     */
    protected function getExtendedAssignmentInformationFactory(): MockObject|AssignmentInformationFactory
    {
        $assignmentInformationFactory = $this->getAssignmentInformationFactory();
        $assignmentInformationFactory->expects($this->any())
            ->method('createAssignmentInformation')
            ->will($this->returnCallback(
                function (
                    $type,
                    $fromDate = null,
                    $toDate = null,
                    array $recursiveMembership = []
                ) {
                    return $this->getAssignmentInformation($type, $fromDate, $toDate, $recursiveMembership);
                }
            ));

        return $assignmentInformationFactory;
    }

    /**
     * @param string $type
     * @param string $id
     * @param bool $deletable
     * @param bool $objectIsMember
     * @param array $ipRange
     * @param string $readAccess
     * @param string $writeAccess
     * @param array $posts
     * @param array $terms
     * @param null $name
      * @return MockObject
     */
    private function getUserGroupType(
        string $type,
        string $id,
        bool   $deletable = true,
        bool   $objectIsMember = false,
        array  $ipRange = [''],
        string $readAccess = 'none',
        string $writeAccess = 'none',
        array  $posts = [],
        array  $terms = [],
               $name = null
    ): MockObject
    {
        $userGroup = $this->createMock($type);

        $userGroup->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        $name = ($name === null) ? "name{$id}" : $name;

        $userGroup->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        $userGroup->expects($this->any())
            ->method('delete')
            ->will($this->returnValue($deletable));

        $userGroup->expects($this->any())
            ->method('isObjectMember')
            ->will($this->returnValue($objectIsMember));

        if ($type === UserGroup::class) {
            $userGroup->expects($this->any())
                ->method('getIpRange')
                ->will($this->returnCallback(function () use ($ipRange) {
                    return implode(';', $ipRange);
                }));

            $userGroup->expects($this->any())
                ->method('getIpRangeArray')
                ->will($this->returnCallback(function () use ($ipRange) {
                    return $ipRange;
                }));
        }

        $userGroup->expects($this->any())
            ->method('getReadAccess')
            ->will($this->returnValue($readAccess));

        $userGroup->expects($this->any())
            ->method('getWriteAccess')
            ->will($this->returnValue($writeAccess));

        $userGroup->expects($this->any())
            ->method('getFullPosts')
            ->will($this->returnValue($posts));

        $userGroup->expects($this->any())
            ->method('getFullTerms')
            ->will($this->returnValue($terms));

        if ($terms !== [] || $posts !== []) {
            $userGroup->expects($this->any())
                ->method('getAssignedObjectsByType')
                ->will($this->returnCallback(function ($type) use ($terms, $posts) {
                    if ($type === ObjectHandler::GENERAL_TERM_OBJECT_TYPE) {
                        return $terms;
                    } elseif ($type === ObjectHandler::GENERAL_POST_OBJECT_TYPE) {
                        return $posts;
                    }

                    return null;
                }));
        }

        return $userGroup;
    }

    /**
     * @param string $id
     * @param bool $deletable
     * @param bool $objectIsMember
     * @param array $ipRange
     * @param string $readAccess
     * @param string $writeAccess
     * @param array $posts
     * @param array $terms
     * @param string|null $name
      * @return MockObject|UserGroup
     */
    protected function getUserGroup(
        string $id,
        bool $deletable = true,
        bool $objectIsMember = false,
        array $ipRange = [''],
        string $readAccess = 'none',
        string $writeAccess = 'none',
        array $posts = [],
        array $terms = [],
        ?string $name = null
    ): UserGroup|MockObject
    {
        return $this->getUserGroupType(
            UserGroup::class,
            $id,
            $deletable,
            $objectIsMember,
            $ipRange,
            $readAccess,
            $writeAccess,
            $posts,
            $terms,
            $name
        );
    }

    /**
     * @param string $type
     * @param string $id
     * @param bool $deletable
     * @param bool $objectIsMember
     * @param array $ipRange
     * @param string $readAccess
     * @param string $writeAccess
     * @param array $posts
     * @param array $terms
     * @param null $name
      * @return UserGroup|MockObject
     */
    protected function getDynamicUserGroup(
        string $type,
        string $id,
        bool   $deletable = true,
        bool   $objectIsMember = false,
        array  $ipRange = [''],
        string $readAccess = 'none',
        string $writeAccess = 'none',
        array  $posts = [],
        array  $terms = [],
               $name = null
    ): UserGroup|MockObject
    {
        return $this->getUserGroupType(
            DynamicUserGroup::class,
            $type.'|'.$id,
            $deletable,
            $objectIsMember,
            $ipRange,
            $readAccess,
            $writeAccess,
            $posts,
            $terms,
            $name
        );
    }

    /**
     * Returns a config parameter mock.
      * @param string $type
     * @param string $postFix
      * @return MockObject|ConfigParameter
     */
    protected function getConfigParameter(string $type, string $postFix = ''): ConfigParameter|MockObject
    {
        $type = strtolower($type);
        $className = ucfirst($type).'ConfigParameter';

        $parameter = $this->createMock("\UserAccessManager\Config\\{$className}");
        $parameter->expects($this->any())
            ->method('getId')
            ->will($this->returnValue("{$type}{$postFix}Id"));
        $parameter->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue("{$type}{$postFix}Value"));

        return $parameter;
    }

    /**
     * @param string|null $type
     * @param string|null $fromDate
     * @param string|null $toDate
     * @param array $recursiveMembership
      * @return MockObject|AssignmentInformation|stdClass
     */
    protected function getAssignmentInformation(
        ?string $type,
        ?string $fromDate = null,
        ?string $toDate = null,
        array $recursiveMembership = []
    ): MockObject|AssignmentInformation|stdClass
    {
        /**
         * @var MockObject|stdClass $assignmentInformation
         */
        $assignmentInformation = $this->createMock(AssignmentInformation::class);

        $assignmentInformation->type = $type;
        $assignmentInformation->fromDate = $fromDate;
        $assignmentInformation->toDate = $toDate;
        $assignmentInformation->recursiveMembership = $recursiveMembership;

        $assignmentInformation->expects($this->any())
            ->method('getType')
            ->will($this->returnValue($type));

        $assignmentInformation->expects($this->any())
            ->method('getFromDate')
            ->will($this->returnValue($fromDate));

        $assignmentInformation->expects($this->any())
            ->method('getToDate')
            ->will($this->returnValue($toDate));

        $assignmentInformation->expects($this->any())
            ->method('getRecursiveMembership')
            ->will($this->returnCallback(function () use (&$assignmentInformation) {
                return $assignmentInformation->recursiveMembership;
            }));

        $assignmentInformation->expects($this->any())
            ->method('setRecursiveMembership')
            ->will($this->returnCallback(function ($newValue) use (&$assignmentInformation) {
                $assignmentInformation->recursiveMembership = $newValue;
            }));

        return $assignmentInformation;
    }

    /**
     * @param string $name
     * @param null|string $classType
     * @return stdClass|MockObject
     */
    protected function createTypeObject(string $name, ?string $classType = null): MockObject|stdClass
    {
        if ($classType !== null) {
            $type = $this->getMockBuilder($classType)->getMock();
        } else {
            $type = new stdClass();
        }

        $type->labels = new stdClass();
        $type->labels->name = $name;

        return $type;
    }


    /**
     * @param string $class
     * @param string $type
     * @param array $falseIds
     * @param array|null $handledObjects
      * @return MockObject
     */
    protected function getMembershipHandler(string $class, string $type, array $falseIds, array $handledObjects = null): MockObject
    {
        $membershipHandler = $this->createMock($class);

        if ($handledObjects === null) {
            $handledObjects = [
                $type => $type,
                'other'.ucfirst($type) => 'other'.ucfirst($type)
            ];
        }

        $membershipHandler->expects($this->any())
            ->method('getHandledObjects')
            ->will($this->returnValue($handledObjects));

        $membershipHandler->expects($this->any())
            ->method('getGeneralObjectType')
            ->will($this->returnValue($type));

        $membershipHandler->expects($this->any())
            ->method('getObjectName')
            ->will($this->returnCallback(
                function (
                    $objectId,
                    &$typeName
                ) use (
                    $type,
                    $falseIds
                ) {
                    if (in_array($objectId, $falseIds) === true) {
                        $assignmentInformation = null;
                        return $objectId;
                    }

                    $typeName = strtolower($type).'TypeName';

                    return strtolower($type).'Name';
                }
            ));

        $membershipHandler->expects($this->any())
            ->method('isMember')
            ->will($this->returnCallback(
                function (
                    AbstractUserGroup $userGroup,
                    $lockRecursive,
                    $objectId,
                    &$assignmentInformation = null
                ) use (
                    $type,
                    $falseIds
                ) {
                    if (in_array($objectId, $falseIds) === true) {
                        $assignmentInformation = null;
                        return false;
                    }

                    $recursiveAssignmentInformation = [];

                    if ($lockRecursive === true) {
                        $recursiveAssignmentInformation = [
                            $this->getAssignmentInformation($type, 'recursiveFromDate', 'recursiveToDate')
                        ];
                    }

                    $assignmentInformation = $this->getAssignmentInformation(
                        $type,
                        'fromDate',
                        'toDate',
                        $recursiveAssignmentInformation
                    );

                    return true;
                }
            ));

        $membershipHandler->expects($this->any())
            ->method('getFullObjects')
            ->will($this->returnCallback(
                function (
                    AbstractUserGroup $userGroup,
                    $lockRecursive,
                    $objectType = null
                ) use ($type) {
                    $return = [1 => $type, 100 => $type.'Other'];

                    if ($lockRecursive === true) {
                        $return[3] = $type;
                        $return[101] = $type.'Other';
                    }

                    if ($objectType !== null) {
                        $return = array_filter($return, function ($element) use ($objectType) {
                            return ($element === $objectType);
                        });
                    }

                    return $return;
                }
            ));

        return $membershipHandler;
    }

    /**
     * @param int $numberOfSites
      * @return array
     */
    protected function getSites(int $numberOfSites = 3): array
    {
        $sites = [];

        for ($count = 1; $count <= $numberOfSites; $count++) {
            /**
             * @var stdClass $site
             */
            $site = $this->getMockBuilder('\WP_Site')->getMock();
            $site->blog_id = $count;
            $sites[] = $site;
        }

        return $sites;
    }

    /**
     * @param int $id
     * @param array $withAdd
     * @param array $withRemove
      * @return MockObject|UserGroup
     */
    protected function getUserGroupWithAddDelete(int $id, array $withAdd = [], array $withRemove = []): UserGroup|MockObject
    {
        $userGroup = $this->getUserGroup($id);

        if (count($withAdd) > 0) {
            $userGroup->expects($this->exactly(count($withAdd)))
                ->method('addObject')
                ->withConsecutive(...$withAdd);
        }

        if (count($withRemove) > 0) {
            $userGroup->expects($this->exactly(count($withRemove)))
                ->method('removeObject')
                ->withConsecutive(...$withRemove);
        }

        return $userGroup;
    }

    /**
     * @param array $addIds
     * @param array $removeIds
     * @param array $with
     * @param array $additional
      * @return array
     */
    protected function getUserGroupArray(array $addIds, array $removeIds = [], array $with = [], array $additional = []): array
    {
        $groups = [];

        $both = array_intersect($addIds, $removeIds);
        $withRemove = array_map(
            function ($element) {
                return array_slice($element, 0, 2);
            },
            $with
        );

        foreach ($both as $id) {
            $groups[$id] = $this->getUserGroupWithAddDelete($id, $with, $withRemove);
        }

        $add = array_diff($addIds, $both);

        foreach ($add as $id) {
            $groups[$id] = $this->getUserGroupWithAddDelete($id, $with, []);
        }

        $remove = array_diff($removeIds, $both);

        foreach ($remove as $id) {
            $groups[$id] = $this->getUserGroupWithAddDelete($id, [], $withRemove);
        }

        foreach ($additional as $id) {
            $group = $this->getUserGroup($id);
            $group->expects($this->never())
                ->method('addObject');

            $groups[$id] = $group;
        }

        return $groups;
    }
}
