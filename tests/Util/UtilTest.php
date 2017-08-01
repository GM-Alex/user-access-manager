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
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Util;

use UserAccessManager\Tests\UserAccessManagerTestCase;
use UserAccessManager\Util\Util;

/**
 * Class UtilTest
 *
 * @package UserAccessManager\Util
 * @coversDefaultClass \UserAccessManager\Util\Util
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
        $util = new Util(
            $this->getPhp()
        );
        self::assertInstanceOf(Util::class, $util);
        return $util;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::startsWith()
     *
     * @param Util $util
     */
    public function testStartsWith($util)
    {
        self::assertTrue($util->startsWith('prefixTestSuffix', 'prefix'));
        self::assertFalse($util->startsWith('prefixTestSuffix', 'prefIx'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::endsWith()
     *
     * @param Util $util
     */
    public function testEndsWith($util)
    {
        self::assertTrue($util->endsWith('prefixTestSuffix', 'Suffix'));
        self::assertFalse($util->endsWith('prefixTestSuffix', 'suffix'));
    }

    /**
     * @group  unit
     * @covers ::getRandomPassword()
     */
    public function testGetRandomPassword()
    {
        $php = $this->getPhp();
        $php->expects($this->once())
            ->method('opensslRandomPseudoBytes')
            ->with(33)
            ->will($this->returnCallback(function ($length, &$strong) {
                $strong = true;
                return "testString{$length}";
            }));

        $util = new Util($php);

        self::assertEquals('dGVzdFN0cmluZzMz', $util->getRandomPassword());

        $returnFunction = function ($length, &$strong) {
            $strong = true;
            return openssl_random_pseudo_bytes($length);
        };

        $php = $this->getPhp();
        $php->expects($this->exactly(2))
            ->method('opensslRandomPseudoBytes')
            ->withConsecutive([33], [11])
            ->will($this->returnCallback($returnFunction));

        $util = new Util($php);

        $randomPassword = $util->getRandomPassword();
        self::assertEquals(32, strlen($randomPassword));

        $randomPassword = $util->getRandomPassword(10);
        self::assertEquals(10, strlen($randomPassword));

        $php = $this->getPhp();
        $php->expects($this->exactly(100))
            ->method('opensslRandomPseudoBytes')
            ->with(33)
            ->will($this->returnCallback($returnFunction));

        $util = new Util($php);

        $passwords = [];

        for ($count = 0; $count < 100; $count++) {
            $randomPassword = $util->getRandomPassword();
            $passwords[$randomPassword] = $randomPassword;
        }

        self::assertEquals(100, count($passwords));
    }

    /**
     * @group                    unit
     * @covers                   ::getRandomPassword()
     * @expectedException        \Exception
     * @expectedExceptionMessage Unable to generate secure token from OpenSSL.
     */
    public function testGetRandomPasswordException()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(1))
            ->method('opensslRandomPseudoBytes')
            ->with(33, false)
            ->will($this->returnCallback(function ($length, &$strong) {
                $strong = false;
                return openssl_random_pseudo_bytes($length);
            }));

        $util = new Util(
            $php
        );

        $util->getRandomPassword();
    }

    /**
     * @group                    unit
     * @covers                   ::getRandomPassword()
     * @expectedException        \Exception
     * @expectedExceptionMessage Unable to generate secure token from OpenSSL.
     */
    public function testSecondGetRandomPasswordSecondException()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(1))
            ->method('opensslRandomPseudoBytes')
            ->with(33, false)
            ->will($this->returnCallback(function ($length, &$strong) {
                $strong = true;
                return false;
            }));

        $util = new Util(
            $php
        );

        $util->getRandomPassword();
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::getCurrentUrl()
     *
     * @param Util $util
     */
    public function testGetCurrentUrl($util)
    {
        $serverTemp = $_SERVER;

        $_SERVER['SERVER_NAME'] = 'serverName';
        $_SERVER['REQUEST_URI'] = null;
        $_SERVER['PHP_SELF'] = '/phpSelf';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PROTOCOL'] = 'http';
        $_SERVER['SERVER_PORT'] = '88';

        self::assertEquals('https://serverName:88/phpSelf', $util->getCurrentUrl());

        $_SERVER['REQUEST_URI'] = '/requestUri';

        self::assertEquals('https://serverName:88/requestUri', $util->getCurrentUrl());

        $_SERVER['HTTPS'] = 'off';

        self::assertEquals('http://serverName:88/requestUri', $util->getCurrentUrl());

        $_SERVER['SERVER_PORT'] = '80';

        self::assertEquals('http://serverName/requestUri', $util->getCurrentUrl());

        $_SERVER['SERVER_PROTOCOL'] = 'ftp';

        self::assertEquals('ftp://serverName/requestUri', $util->getCurrentUrl());

        $_SERVER = $serverTemp;
    }
}
