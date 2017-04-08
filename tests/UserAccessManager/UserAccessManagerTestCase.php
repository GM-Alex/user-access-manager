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
 * @version   SVN: $Id$
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
     * @param object $Object
     * @param string $sMethodName
     * @param array  $aArguments
     *
     * @return mixed
     */
    public static function callMethod($Object, $sMethodName, array $aArguments = [])
    {
        $Class = new \ReflectionClass($Object);
        $Method = $Class->getMethod($sMethodName);
        $Method->setAccessible(true);
        return $Method->invokeArgs($Object, $aArguments);
    }

    /**
     * Sets a private property
     *
     * @param object $Object
     * @param string $sValueName
     * @param mixed  $mValue
     */
    public static function setValue($Object, $sValueName, $mValue)
    {
        $Reflection = new \ReflectionClass($Object);
        $Property = $Reflection->getProperty($sValueName);
        $Property->setAccessible(true);
        $Property->setValue($Object, $mValue);
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
     * @param int    $iId
     * @param bool   $blDeletable
     * @param bool   $blObjectIsMember
     * @param array  $aIpRange
     * @param string $sReadAccess
     * @param string $sWriteAccess
     * @param array  $aPosts
     * @param array  $aTerms
     * @param string $sName
     *
     * @return \UserAccessManager\UserGroup\UserGroup|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getUserGroup(
        $iId,
        $blDeletable = true,
        $blObjectIsMember = false,
        array $aIpRange = [''],
        $sReadAccess = 'none',
        $sWriteAccess = 'none',
        array $aPosts = [],
        array $aTerms = [],
        $sName = null
    ) {
        $UserGroup = $this->createMock('\UserAccessManager\UserGroup\UserGroup');
        self::setValue($UserGroup, 'iId', $iId);

        $UserGroup->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($iId));

        $sName = ($sName === null) ? "name{$iId}" : $sName;

        $UserGroup->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($sName));

        $UserGroup->expects($this->any())
            ->method('delete')
            ->will($this->returnValue($blDeletable));

        $UserGroup->expects($this->any())
            ->method('isObjectMember')
            ->will($this->returnValue($blObjectIsMember));

        $UserGroup->expects($this->any())
            ->method('getIpRange')
            ->will($this->returnCallback(function ($blString) use ($aIpRange) {
                return ($blString === false) ? $aIpRange : implode(';', $aIpRange);
            }));

        $UserGroup->expects($this->any())
            ->method('getReadAccess')
            ->will($this->returnValue($sReadAccess));

        $UserGroup->expects($this->any())
            ->method('getWriteAccess')
            ->will($this->returnValue($sWriteAccess));

        $UserGroup->expects($this->any())
            ->method('getFullPosts')
            ->will($this->returnValue($aPosts));

        $UserGroup->expects($this->any())
            ->method('getFullTerms')
            ->will($this->returnValue($aTerms));

        return $UserGroup;
    }
}
