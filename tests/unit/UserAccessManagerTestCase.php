<?php


class UserAccessManagerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Calls a private or protected object method.
     *
     * @param object $oObject
     * @param string $sMethodName
     * @param array  $aArguments
     *
     * @return mixed
     */
    public static function callMethod($oObject, $sMethodName, array $aArguments = [])
    {
        $oClass = new \ReflectionClass($oObject);
        $oMethod = $oClass->getMethod($sMethodName);
        $oMethod->setAccessible(true);
        return $oMethod->invokeArgs($oObject, $aArguments);
    }

    /**
     * Sets a private property
     *
     * @param object $oObject
     * @param string $sValueName
     * @param mixed  $mValue
     */
    public static function setValue($oObject, $sValueName, $mValue)
    {
        $oReflection = new \ReflectionClass($oObject);
        $oProperty = $oReflection->getProperty($sValueName);
        $oProperty->setAccessible(true);
        $oProperty->setValue($oObject, $mValue);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Wordpress
     */
    protected function getWrapper()
    {
        return $this->createMock('\UserAccessManager\Wrapper\Wordpress');
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
}