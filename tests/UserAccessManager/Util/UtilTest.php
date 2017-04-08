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

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class UtilTest
 *
 * @package UserAccessManager\Util
 */
class UtilTest extends UserAccessManagerTestCase
{
    /**
     * @group unit
     *
     * @return Util
     */
    public function testCanCreateInstance()
    {
        $oUtil = new Util(
            $this->getPhp()
        );
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
        self::assertTrue($oUtil->startsWith('prefixTestSuffix', 'prefix'));
        self::assertFalse($oUtil->startsWith('prefixTestSuffix', 'prefIx'));
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
        self::assertTrue($oUtil->endsWith('prefixTestSuffix', 'Suffix'));
        self::assertFalse($oUtil->endsWith('prefixTestSuffix', 'suffix'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Util\Util::getRandomPassword()
     */
    public function testGetRandomPassword()
    {
        $oPhp = $this->getPhp();
        $oPhp->expects($this->once())
            ->method('opensslRandomPseudoBytes')
            ->with(33)
            ->will($this->returnCallback(function($iLength, &$blStrong) {
                $blStrong = true;
                return "testString{$iLength}";
            }));

        $oUtil = new Util($oPhp);

        self::assertEquals('dGVzdFN0cmluZzMz', $oUtil->getRandomPassword());

        $cReturnFunction = function($iLength, &$blStrong) {
            $blStrong = true;
            return openssl_random_pseudo_bytes($iLength);
        };

        $oPhp = $this->getPhp();
        $oPhp->expects($this->exactly(2))
            ->method('opensslRandomPseudoBytes')
            ->withConsecutive([33], [11])
            ->will($this->returnCallback($cReturnFunction));

        $oUtil = new Util($oPhp);

        $sRandomPassword = $oUtil->getRandomPassword();
        self::assertEquals(32, strlen($sRandomPassword));

        $sRandomPassword = $oUtil->getRandomPassword(10);
        self::assertEquals(10, strlen($sRandomPassword));

        $oPhp = $this->getPhp();
        $oPhp->expects($this->exactly(100))
            ->method('opensslRandomPseudoBytes')
            ->with(33)
            ->will($this->returnCallback($cReturnFunction));

        $oUtil = new Util($oPhp);

        $aPasswords = [];

        for ($iCount = 0; $iCount < 100; $iCount++) {
            $sRandomPassword = $oUtil->getRandomPassword();
            $aPasswords[$sRandomPassword] = $sRandomPassword;
        }

        self::assertEquals(100, count($aPasswords));
    }

    /**
     * @group                    unit
     * @covers                   \UserAccessManager\Util\Util::getRandomPassword()
     * @expectedException        \Exception
     * @expectedExceptionMessage Unable to generate secure token from OpenSSL.
     */
    public function testGetRandomPasswordException()
    {
        $oPhp = $this->getPhp();
        $oPhp->expects($this->exactly(1))
            ->method('opensslRandomPseudoBytes')
            ->with(33, false)
            ->will($this->returnCallback(function($iLength, &$blStrong) {
                $blStrong = false;
                return openssl_random_pseudo_bytes($iLength);
            }));

        $oUtil = new Util(
            $oPhp
        );

        $oUtil->getRandomPassword();
    }

    /**
     * @group                    unit
     * @covers                   \UserAccessManager\Util\Util::getRandomPassword()
     * @expectedException        \Exception
     * @expectedExceptionMessage Unable to generate secure token from OpenSSL.
     */
    public function testSecondGetRandomPasswordSecondException()
    {
        $oPhp = $this->getPhp();
        $oPhp->expects($this->exactly(1))
            ->method('opensslRandomPseudoBytes')
            ->with(33, false)
            ->will($this->returnCallback(function ($iLength, &$blStrong) {
                $blStrong = true;
                return false;
            }));

        $oUtil = new Util(
            $oPhp
        );

        $oUtil->getRandomPassword();
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
