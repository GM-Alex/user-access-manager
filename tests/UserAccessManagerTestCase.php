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
namespace UserAccessManager\Tests;

use UserAccessManager\AccessHandler\AccessHandler;
use UserAccessManager\Cache\Cache;
use UserAccessManager\Cache\CacheProviderFactory;
use UserAccessManager\Cache\FileSystemCacheProvider;
use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigFactory;
use UserAccessManager\Config\ConfigParameterFactory;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Controller\ControllerFactory;
use UserAccessManager\Database\Database;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\FileHandler\FileObjectFactory;
use UserAccessManager\FileHandler\FileProtectionFactory;
use UserAccessManager\Form\FormFactory;
use UserAccessManager\Form\FormHelper;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\SetupHandler\SetupHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\UserGroup\AssignmentInformation;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\UserGroup\DynamicUserGroup;
use UserAccessManager\ObjectMembership\ObjectMembershipHandlerFactory;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\UserGroup\UserGroupFactory;
use UserAccessManager\Util\Util;
use UserAccessManager\Widget\WidgetFactory;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;
use UserAccessManager\Wrapper\WordpressCli;

/**
 * Class UserAccessManagerTestCase
 */
abstract class UserAccessManagerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Calls a private or protected object method.
     *
     * @param object $object
     * @param string $methodName
     * @param array  $arguments
     *
     * @return mixed
     */
    public static function callMethod($object, $methodName, array $arguments = [])
    {
        $class = new \ReflectionClass($object);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $arguments);
    }

    /**
     * Sets a private property
     *
     * @param object $object
     * @param string $valueName
     * @param mixed  $value
     */
    public static function setValue($object, $valueName, $value)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($valueName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Php
     */
    protected function getPhp()
    {
        return $this->createMock(Php::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Wordpress
     */
    protected function getWordpress()
    {
        return $this->createMock(Wordpress::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|WordpressCli
     */
    protected function getWordpressCli()
    {
        return $this->createMock(WordpressCli::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Config
     */
    protected function getConfig()
    {
        return $this->createMock(Config::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MainConfig
     */
    protected function getMainConfig()
    {
        return $this->createMock(MainConfig::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Database
     */
    protected function getDatabase()
    {
        return $this->createMock(Database::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Util
     */
    protected function getUtil()
    {
        return $this->createMock(Util::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Cache
     */
    protected function getCache()
    {
        return $this->createMock(Cache::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheProviderFactory
     */
    protected function getCacheProviderFactory()
    {
        return $this->createMock(CacheProviderFactory::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigFactory
     */
    protected function getConfigFactory()
    {
        return $this->createMock(ConfigFactory::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FileSystemCacheProvider
     */
    protected function getFileSystemCacheProvider()
    {
        return $this->createMock(FileSystemCacheProvider::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectHandler
     */
    protected function getObjectHandler()
    {
        return $this->createMock(ObjectHandler::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AccessHandler
     */
    protected function getAccessHandler()
    {
        return $this->createMock(AccessHandler::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SetupHandler
     */
    protected function getSetupHandler()
    {
        return $this->createMock(SetupHandler::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ControllerFactory
     */
    protected function getControllerFactory()
    {
        return $this->createMock(ControllerFactory::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|WidgetFactory
     */
    protected function getWidgetFactory()
    {
        return $this->createMock(WidgetFactory::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FileHandler
     */
    protected function getFileHandler()
    {
        return $this->createMock(FileHandler::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FileObjectFactory
     */
    protected function getFileObjectFactory()
    {
        return $this->createMock(FileObjectFactory::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|UserGroupFactory
     */
    protected function getUserGroupFactory()
    {
        return $this->createMock(UserGroupFactory::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConfigParameterFactory
     */
    protected function getConfigParameterFactory()
    {
        return $this->createMock(ConfigParameterFactory::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FileProtectionFactory
     */
    protected function getFileProtectionFactory()
    {
        return $this->createMock(FileProtectionFactory::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FormFactory
     */
    protected function getFormFactory()
    {
        return $this->createMock(FormFactory::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FormHelper
     */
    protected function getFormHelper()
    {
        return $this->createMock(FormHelper::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|UserAccessManager
     */
    protected function getUserAccessManager()
    {
        return $this->createMock(UserAccessManager::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectMembershipHandlerFactory
     */
    protected function getObjectMembershipHandlerFactory()
    {
        return $this->createMock(ObjectMembershipHandlerFactory::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AssignmentInformationFactory
     */
    protected function getAssignmentInformationFactory()
    {
        return $this->createMock(AssignmentInformationFactory::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AssignmentInformationFactory
     */
    protected function getExtendedAssignmentInformationFactory()
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
     * @param bool   $deletable
     * @param bool   $objectIsMember
     * @param array  $ipRange
     * @param string $readAccess
     * @param string $writeAccess
     * @param array  $posts
     * @param array  $terms
     * @param null   $name
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getUserGroupType(
        $type,
        $id,
        $deletable = true,
        $objectIsMember = false,
        array $ipRange = [''],
        $readAccess = 'none',
        $writeAccess = 'none',
        array $posts = [],
        array $terms = [],
        $name = null
    ) {
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
     * @param bool   $deletable
     * @param bool   $objectIsMember
     * @param array  $ipRange
     * @param string $readAccess
     * @param string $writeAccess
     * @param array  $posts
     * @param array  $terms
     * @param string $name
     *
     * @return \UserAccessManager\UserGroup\UserGroup|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getUserGroup(
        $id,
        $deletable = true,
        $objectIsMember = false,
        array $ipRange = [''],
        $readAccess = 'none',
        $writeAccess = 'none',
        array $posts = [],
        array $terms = [],
        $name = null
    ) {
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
     * @param bool   $deletable
     * @param bool   $objectIsMember
     * @param array  $ipRange
     * @param string $readAccess
     * @param string $writeAccess
     * @param array  $posts
     * @param array  $terms
     * @param string $name
     *
     * @return \UserAccessManager\UserGroup\UserGroup|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDynamicUserGroup(
        $type,
        $id,
        $deletable = true,
        $objectIsMember = false,
        array $ipRange = [''],
        $readAccess = 'none',
        $writeAccess = 'none',
        array $posts = [],
        array $terms = [],
        $name = null
    ) {
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
     *
     * @param string $type
     * @param string $postFix
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\ConfigParameter
     */
    protected function getConfigParameter($type, $postFix = '')
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
     * @param string $type
     * @param string $fromDate
     * @param string $toDate
     * @param array  $recursiveMembership
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\UserGroup\AssignmentInformation|\stdClass
     */
    protected function getAssignmentInformation(
        $type,
        $fromDate = null,
        $toDate = null,
        array $recursiveMembership = []
    ) {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $assignmentInformation
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
     * @param string      $name
     * @param null|string $classType
     *
     * @return mixed
     */
    protected function createTypeObject($name, $classType = null)
    {
        if ($classType !== null) {
            $type = $this->getMockBuilder($classType)->getMock();
        } else {
            $type = new \stdClass();
        }

        $type->labels = new \stdClass();
        $type->labels->name = $name;

        return $type;
    }

    
    /**
     * @param string $class
     * @param string $type
     * @param array  $falseIds
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMembershipHandler($class, $type, array $falseIds)
    {
        $membershipHandler = $this->createMock($class);

        $membershipHandler->expects($this->any())
            ->method('getHandledObjects')
            ->will($this->returnValue([
                $type => $type,
                'other'.ucfirst($type) => 'other'.ucfirst($type)
            ]));

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
}
