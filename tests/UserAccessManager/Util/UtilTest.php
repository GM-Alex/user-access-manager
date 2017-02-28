<?php
/**
 * UtilTest.php
 *
 * The UtilTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Util;

/**
 * Class UtilTest
 *
 * @package UserAccessManager\Util
 */
class UtilTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group unit
     *
     * @return Util
     */
    public function testCanCreateInstance()
    {
        $oUtil = new Util();
        self::assertInstanceOf('\UserAccessManager\Util\Util', $oUtil);
        return $oUtil;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Util\Util::startsWith()
     *
     * @param Util $oUtil
     */
    public function testStartsWith($oUtil)
    {
        self::assertEquals(true, $oUtil->startsWith('prefixTestSuffix', 'prefix'));
        self::assertEquals(false, $oUtil->startsWith('prefixTestSuffix', 'prefIx'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Util\Util::endsWith()
     *
     * @param Util $oUtil
     */
    public function testEndsWith($oUtil)
    {
        self::assertEquals(true, $oUtil->endsWith('prefixTestSuffix', 'Suffix'));
        self::assertEquals(false, $oUtil->endsWith('prefixTestSuffix', 'suffix'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Util\Util::getRandomPassword()
     *
     * @param Util $oUtil
     */
    public function testGetRandomPassword($oUtil)
    {
        $sRandomPassword = $oUtil->getRandomPassword();
        self::assertEquals(32, strlen($sRandomPassword));

        $sRandomPassword = $oUtil->getRandomPassword(10);
        self::assertEquals(10, strlen($sRandomPassword));

        $aPasswords = [];

        for ($iCount = 0; $iCount < 100; $iCount++) {
            $sRandomPassword = $oUtil->getRandomPassword();
            $aPasswords[$sRandomPassword] = $sRandomPassword;
        }

        self::assertEquals(100, count($aPasswords));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Util\Util::getCurrentUrl()
     *
     * @param Util $oUtil
     */
    public function testGetCurrentUrl($oUtil)
    {
        $aServerTemp = $_SERVER;

        $_SERVER['SERVER_NAME'] = 'serverName';
        $_SERVER['REQUEST_URI'] = null;
        $_SERVER['PHP_SELF'] = '/phpSelf';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PROTOCOL'] = 'http';
        $_SERVER['SERVER_PORT'] = '88';

        self::assertEquals('https://serverName:88/phpSelf', $oUtil->getCurrentUrl());

        $_SERVER['REQUEST_URI'] = '/requestUri';

        self::assertEquals('https://serverName:88/requestUri', $oUtil->getCurrentUrl());

        $_SERVER['HTTPS'] = 'off';

        self::assertEquals('http://serverName:88/requestUri', $oUtil->getCurrentUrl());

        $_SERVER['SERVER_PORT'] = '80';

        self::assertEquals('http://serverName/requestUri', $oUtil->getCurrentUrl());

        $_SERVER['SERVER_PROTOCOL'] = 'ftp';

        self::assertEquals('ftp://serverName/requestUri', $oUtil->getCurrentUrl());

        $_SERVER = $aServerTemp;
    }
}
