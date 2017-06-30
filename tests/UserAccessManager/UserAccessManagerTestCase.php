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
namespace UserAccessManager;
use UserAccessManager\UserGroup\DynamicUserGroup;
use UserAccessManager\UserGroup\UserGroup;

/**
 * Class UserAccessManagerTestCase
 */
class UserAccessManagerTestCase extends \PHPUnit_Framework_TestCase
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
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Php
     */
    protected function getPhp()
    {
        return $this->createMock('\UserAccessManager\Wrapper\Php');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Wordpress
     */
    protected function getWordpress()
    {
        return $this->createMock('\UserAccessManager\Wrapper\Wordpress');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\WordpressCli
     */
    protected function getWordpressCli()
    {
        return $this->createMock('\UserAccessManager\Wrapper\WordpressCli');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\Config
     */
    protected function getConfig()
    {
        return $this->createMock('\UserAccessManager\Config\Config');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\MainConfig
     */
    protected function getMainConfig()
    {
        return $this->createMock('\UserAccessManager\Config\MainConfig');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Database\Database
     */
    protected function getDatabase()
    {
        return $this->createMock('\UserAccessManager\Database\Database');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Util\Util
     */
    protected function getUtil()
    {
        return $this->createMock('\UserAccessManager\Util\Util');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Cache\Cache
     */
    protected function getCache()
    {
        return $this->createMock('\UserAccessManager\Cache\Cache');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Cache\CacheProviderFactory
     */
    protected function getCacheProviderFactory()
    {
        return $this->createMock('\UserAccessManager\Cache\CacheProviderFactory');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\ConfigFactory
     */
    protected function getConfigFactory()
    {
        return $this->createMock('\UserAccessManager\Config\ConfigFactory');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Cache\FileSystemCacheProvider
     */
    protected function getFileSystemCacheProvider()
    {
        return $this->createMock('\UserAccessManager\Cache\FileSystemCacheProvider');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\ObjectHandler\ObjectHandler
     */
    protected function getObjectHandler()
    {
        return $this->createMock('\UserAccessManager\ObjectHandler\ObjectHandler');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\AccessHandler\AccessHandler
     */
    protected function getAccessHandler()
    {
        return $this->createMock('\UserAccessManager\AccessHandler\AccessHandler');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\SetupHandler\SetupHandler
     */
    protected function getSetupHandler()
    {
        return $this->createMock('\UserAccessManager\SetupHandler\SetupHandler');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Controller\ControllerFactory
     */
    protected function getControllerFactory()
    {
        return $this->createMock('\UserAccessManager\Controller\ControllerFactory');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\FileHandler\FileHandler
     */
    protected function getFileHandler()
    {
        return $this->createMock('\UserAccessManager\FileHandler\FileHandler');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\FileHandler\FileObjectFactory
     */
    protected function getFileObjectFactory()
    {
        return $this->createMock('\UserAccessManager\FileHandler\FileObjectFactory');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\UserGroup\UserGroupFactory
     */
    protected function getUserGroupFactory()
    {
        return $this->createMock('\UserAccessManager\UserGroup\UserGroupFactory');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\ConfigParameterFactory
     */
    protected function getConfigParameterFactory()
    {
        return $this->createMock('\UserAccessManager\Config\ConfigParameterFactory');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\FileHandler\FileProtectionFactory
     */
    protected function getFileProtectionFactory()
    {
        return $this->createMock('\UserAccessManager\FileHandler\FileProtectionFactory');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Form\FormFactory
     */
    protected function getFormFactory()
    {
        return $this->createMock('\UserAccessManager\Form\FormFactory');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Form\FormHelper
     */
    protected function getFormHelper()
    {
        return $this->createMock('\UserAccessManager\Form\FormHelper');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\UserAccessManager
     */
    protected function getUserAccessManager()
    {
        return $this->createMock('\UserAccessManager\UserAccessManager');
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
}
