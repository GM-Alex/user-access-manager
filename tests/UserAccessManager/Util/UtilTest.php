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
        $Util = new Util(
            $this->getPhp()
        );
        self::assertInstanceOf('\UserAccessManager\Util\Util', $Util);
        return $Util;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Util\Util::startsWith()
     *
     * @param Util $Util
     */
    public function testStartsWith($Util)
    {
        self::assertTrue($Util->startsWith('prefixTestSuffix', 'prefix'));
        self::assertFalse($Util->startsWith('prefixTestSuffix', 'prefIx'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Util\Util::endsWith()
     *
     * @param Util $Util
     */
    public function testEndsWith($Util)
    {
        self::assertTrue($Util->endsWith('prefixTestSuffix', 'Suffix'));
        self::assertFalse($Util->endsWith('prefixTestSuffix', 'suffix'));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Util\Util::getRandomPassword()
     */
    public function testGetRandomPassword()
    {
        $Php = $this->getPhp();
        $Php->expects($this->once())
            ->method('opensslRandomPseudoBytes')
            ->with(33)
            ->will($this->returnCallback(function ($iLength, &$blStrong) {
                $blStrong = true;
                return "testString{$iLength}";
            }));

        $Util = new Util($Php);

        self::assertEquals('dGVzdFN0cmluZzMz', $Util->getRandomPassword());

        $cReturnFunction = function ($iLength, &$blStrong) {
            $blStrong = true;
            return openssl_random_pseudo_bytes($iLength);
        };

        $Php = $this->getPhp();
        $Php->expects($this->exactly(2))
            ->method('opensslRandomPseudoBytes')
            ->withConsecutive([33], [11])
            ->will($this->returnCallback($cReturnFunction));

        $Util = new Util($Php);

        $sRandomPassword = $Util->getRandomPassword();
        self::assertEquals(32, strlen($sRandomPassword));

        $sRandomPassword = $Util->getRandomPassword(10);
        self::assertEquals(10, strlen($sRandomPassword));

        $Php = $this->getPhp();
        $Php->expects($this->exactly(100))
            ->method('opensslRandomPseudoBytes')
            ->with(33)
            ->will($this->returnCallback($cReturnFunction));

        $Util = new Util($Php);

        $aPasswords = [];

        for ($iCount = 0; $iCount < 100; $iCount++) {
            $sRandomPassword = $Util->getRandomPassword();
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
        $Php = $this->getPhp();
        $Php->expects($this->exactly(1))
            ->method('opensslRandomPseudoBytes')
            ->with(33, false)
            ->will($this->returnCallback(function ($iLength, &$blStrong) {
                $blStrong = false;
                return openssl_random_pseudo_bytes($iLength);
            }));

        $Util = new Util(
            $Php
        );

        $Util->getRandomPassword();
    }

    /**
     * @group                    unit
     * @covers                   \UserAccessManager\Util\Util::getRandomPassword()
     * @expectedException        \Exception
     * @expectedExceptionMessage Unable to generate secure token from OpenSSL.
     */
    public function testSecondGetRandomPasswordSecondException()
    {
        $Php = $this->getPhp();
        $Php->expects($this->exactly(1))
            ->method('opensslRandomPseudoBytes')
            ->with(33, false)
            ->will($this->returnCallback(function ($iLength, &$blStrong) {
                $blStrong = true;
                return false;
            }));

        $Util = new Util(
            $Php
        );

        $Util->getRandomPassword();
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Util\Util::getCurrentUrl()
     *
     * @param Util $Util
     */
    public function testGetCurrentUrl($Util)
    {
        $aServerTemp = $_SERVER;

        $_SERVER['SERVER_NAME'] = 'serverName';
        $_SERVER['REQUEST_URI'] = null;
        $_SERVER['PHP_SELF'] = '/phpSelf';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PROTOCOL'] = 'http';
        $_SERVER['SERVER_PORT'] = '88';

        self::assertEquals('https://serverName:88/phpSelf', $Util->getCurrentUrl());

        $_SERVER['REQUEST_URI'] = '/requestUri';

        self::assertEquals('https://serverName:88/requestUri', $Util->getCurrentUrl());

        $_SERVER['HTTPS'] = 'off';

        self::assertEquals('http://serverName:88/requestUri', $Util->getCurrentUrl());

        $_SERVER['SERVER_PORT'] = '80';

        self::assertEquals('http://serverName/requestUri', $Util->getCurrentUrl());

        $_SERVER['SERVER_PROTOCOL'] = 'ftp';

        self::assertEquals('ftp://serverName/requestUri', $Util->getCurrentUrl());

        $_SERVER = $aServerTemp;
    }
}
