<?php


class UserAccessManagerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Calls a private or protected object method.
     *
     * @param string $oObject
     * @param string $sMethodName
     * @param array  $aArguments
     *
     * @return mixed
     */
    public static function callMethod($oObject, $sMethodName, array $aArguments = array())
    {
        $oClass = new \ReflectionClass($oObject);
        $oMethod = $oClass->getMethod($sMethodName);
        $oMethod->setAccessible(true);
        return $oMethod->invokeArgs($oObject, $aArguments);
    }
}