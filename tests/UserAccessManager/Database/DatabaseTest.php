<?php
/**
 * DatabaseTest.php
 *
 * The DatabaseTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Database;

/**
 * Class DatabaseTest
 *
 * @package UserAccessManager\Database
 */
class DatabaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Wordpress $oWrapper
     */
    private $_oDefaultWrapper;

    /**
     * Create default mocked objects.
     */
    public function setUp()
    {
        $this->_oDefaultWrapper = $this->createMock('\UserAccessManager\Wrapper\Wordpress');
    }

    /**
     * @group unit
     * @covers \UserAccessManager\Database\Database::__construct()
     */
    public function testCanCreateInstance()
    {
        $oDatabase = new Database($this->_oDefaultWrapper);
        self::assertInstanceOf('\UserAccessManager\Database\Database', $oDatabase);
    }

    /**
     * @group unit
     * @covers \UserAccessManager\Database\Database::getUserGroupTable()
     */
    public function testGetUserGroupTable()
    {
        $oSimpleWpDatabase = new \stdClass();
        $oSimpleWpDatabase->prefix = 'wp_';

        $oWrapper = clone $this->_oDefaultWrapper;
        $oWrapper->expects($this->once())
            ->method('getDatabase')
            ->will($this->returnValue($oSimpleWpDatabase));

        $oDatabase = new Database($oWrapper);

        self::assertEquals('wp_uam_accessgroups', $oDatabase->getUserGroupTable());
    }

    /**
     * @group unit
     * @covers \UserAccessManager\Database\Database::getUserGroupToObjectTable()
     */
    public function testGetUserGroupToObjectTable()
    {
        $oSimpleWpDatabase = new \stdClass();
        $oSimpleWpDatabase->prefix = 'wp_';

        $oWrapper = clone $this->_oDefaultWrapper;
        $oWrapper->expects($this->once())
            ->method('getDatabase')
            ->will($this->returnValue($oSimpleWpDatabase));

        $oDatabase = new Database($oWrapper);

        self::assertEquals('wp_uam_accessgroup_to_object', $oDatabase->getUserGroupToObjectTable());
    }

    public function testGetCharset()
    {

    }

    public function testGenerateSqlIdList()
    {

    }
}
