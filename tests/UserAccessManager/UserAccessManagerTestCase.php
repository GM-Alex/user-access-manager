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
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\UserGroup\UserGroupFactory
     */
    protected function getUserGroupFactory()
    {
        return $this->createMock('\UserAccessManager\UserGroup\UserGroupFactory');
    }

    /**
     * @param int    $id
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
        $userGroup = $this->createMock('\UserAccessManager\UserGroup\UserGroup');
        self::setValue($userGroup, 'id', $id);

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
            ->will($this->returnCallback(function ($string) use ($ipRange) {
                return ($string === false) ? $ipRange : implode(';', $ipRange);
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
}
