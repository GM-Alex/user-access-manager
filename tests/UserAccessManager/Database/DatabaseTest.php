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

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class DatabaseTest
 *
 * @package UserAccessManager\Database
 */
class DatabaseTest extends UserAccessManagerTestCase
{
    /**
     * @param array $aMethods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\wpdb
     */
    private function getWpDatabase(array $aMethods = [])
    {
        return $this->getMockBuilder('\wpdb')
            ->setMethods($aMethods)
            ->getMock();
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $WpDatabase
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Wordpress
     */
    private function getWrapperWithWpDatabase($WpDatabase)
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('getDatabase')
            ->will($this->returnValue($WpDatabase));

        return $Wordpress;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::__construct()
     */
    public function testCanCreateInstance()
    {
        $Database = new Database($this->getWordpress());
        self::assertInstanceOf('\UserAccessManager\Database\Database', $Database);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::getUserGroupTable()
     */
    public function testGetUserGroupTable()
    {
        $SimpleWpDatabase = new \stdClass();
        $SimpleWpDatabase->prefix = 'wp_';

        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->once())
            ->method('getDatabase')
            ->will($this->returnValue($SimpleWpDatabase));

        $Database = new Database($Wordpress);

        self::assertEquals('wp_uam_accessgroups', $Database->getUserGroupTable());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::getUserGroupToObjectTable()
     */
    public function testGetUserGroupToObjectTable()
    {
        $WpDatabase = $this->getWpDatabase();
        $WpDatabase->prefix = 'wp_';
        $Wordpress = $this->getWrapperWithWpDatabase($WpDatabase);
        $Database = new Database($Wordpress);

        self::assertEquals('wp_uam_accessgroup_to_object', $Database->getUserGroupToObjectTable());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::dbDelta()
     */
    public function testDbDelta()
    {
        $WpDatabase = $this->getWpDatabase();
        $Wordpress = $this->getWrapperWithWpDatabase($WpDatabase);
        $Wordpress->expects($this->exactly(2))
            ->method('dbDelta')
            ->withConsecutive(['', true], ['query', false])
            ->will($this->onConsecutiveCalls('firstReturn', 'secondReturn'));

        $Database = new Database($Wordpress);
        self::assertEquals('firstReturn', $Database->dbDelta());
        self::assertEquals('secondReturn', $Database->dbDelta('query', false));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::getPrefix()
     * @covers \UserAccessManager\Database\Database::getLastInsertId()
     * @covers \UserAccessManager\Database\Database::getCurrentBlogId()
     * @covers \UserAccessManager\Database\Database::getBlogsTable()
     * @covers \UserAccessManager\Database\Database::getPostsTable()
     * @covers \UserAccessManager\Database\Database::getTermRelationshipsTable()
     * @covers \UserAccessManager\Database\Database::getTermTaxonomyTable()
     * @covers \UserAccessManager\Database\Database::getUsersTable()
     * @covers \UserAccessManager\Database\Database::getCapabilitiesTable()
     */
    public function testSimpleGetters()
    {
        $WpDatabase = $this->getWpDatabase();
        $WpDatabase->prefix = 'prefix_';
        $WpDatabase->insert_id = 'insert_id';
        $WpDatabase->blogid = 'blogid';
        $WpDatabase->blogs = 'blogs';
        $WpDatabase->posts = 'posts';
        $WpDatabase->term_relationships = 'term_relationships';
        $WpDatabase->term_taxonomy = 'term_taxonomy';
        $WpDatabase->users = 'users';

        $Wordpress = $this->getWrapperWithWpDatabase($WpDatabase);
        $Database = new Database($Wordpress);

        self::assertEquals('prefix_', $Database->getPrefix());
        self::assertEquals('insert_id', $Database->getLastInsertId());
        self::assertEquals('blogid', $Database->getCurrentBlogId());
        self::assertEquals('blogs', $Database->getBlogsTable());
        self::assertEquals('posts', $Database->getPostsTable());
        self::assertEquals('term_relationships', $Database->getTermRelationshipsTable());
        self::assertEquals('term_taxonomy', $Database->getTermTaxonomyTable());
        self::assertEquals('users', $Database->getUsersTable());
        self::assertEquals('prefix_capabilities', $Database->getCapabilitiesTable());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::getColumn()
     * @covers \UserAccessManager\Database\Database::getRow()
     * @covers \UserAccessManager\Database\Database::getVariable()
     * @covers \UserAccessManager\Database\Database::getBlogPrefix()
     * @covers \UserAccessManager\Database\Database::prepare()
     * @covers \UserAccessManager\Database\Database::query()
     * @covers \UserAccessManager\Database\Database::getResults()
     * @covers \UserAccessManager\Database\Database::insert()
     * @covers \UserAccessManager\Database\Database::update()
     * @covers \UserAccessManager\Database\Database::delete()
     */
    public function testFunctionWrapper()
    {
        $aWrapperFunctions = [
            'getColumn' => [
                'get_col',
                [[null, 0], ['query', 10]],
                ['firstReturn', 'secondReturn'],
                [[], ['query', 10]]
            ],
            'getRow' => [
                'get_row',
                [[null, OBJECT, 0], ['query', 'testObject', 10]],
                ['firstReturn', 'secondReturn'],
                [[], ['query', 'testObject', 10]]
            ],
            'getVariable' => [
                'get_var',
                [[null, 0, 0], ['query', 10, 20]],
                ['firstReturn', 'secondReturn'],
                [[], ['query', 10, 20]]
            ],
            'getBlogPrefix' => [
                'get_blog_prefix',
                [[null], [10]],
                ['firstReturn', 'secondReturn'],
                [[], [10]]
            ],
            'prepare' => [
                'prepare',
                [['query', ['a']]],
                ['firstReturn'],
                [['query', ['a']]]
            ],
            'query' => [
                'query',
                [['query']],
                ['firstReturn'],
                [['query']]
            ],
            'getResults' => [
                'get_results',
                [[null, OBJECT], ['query', 'testObject']],
                ['firstReturn', 'secondReturn'],
                [[], ['query', 'testObject']]
            ],
            'insert' => [
                'insert',
                [['table', ['a'], null], ['table', ['b'], 'format']],
                ['firstReturn', 'secondReturn'],
                [['table', ['a']], ['table', ['b'], 'format']]
            ],
            'update' => [
                'update',
                [['table', ['a'], ['b'], null, null], ['table', ['c'], ['d'], 'format', 'where']],
                ['firstReturn', 'secondReturn'],
                [['table', ['a'], ['b']], ['table', ['c'], ['d'], 'format', 'where']]
            ],
            'delete' => [
                'delete',
                [['table', ['a'], null], ['table', ['b'], 'where']],
                ['firstReturn', 'secondReturn'],
                [['table', ['a']], ['table', ['b'], 'where']]
            ]
        ];

        foreach ($aWrapperFunctions as $sFunctionName => $aTestValues) {
            $sWrapperFunction = $aTestValues[0];
            $aExpectedArguments = $aTestValues[1];
            $aExpectedResults = $aTestValues[2];
            $aArguments = $aTestValues[3];

            $WpDatabase = $this->getWpDatabase([$sWrapperFunction]);
            $WpDatabase->expects($this->exactly(count($aExpectedResults)))
                ->method($sWrapperFunction)
                ->withConsecutive(...$aExpectedArguments)
                ->will($this->onConsecutiveCalls(...$aExpectedResults));

            $Wordpress = $this->getWrapperWithWpDatabase($WpDatabase);
            $Database = new Database($Wordpress);

            foreach ($aExpectedResults as $sKey => $mExpectedValue) {
                self::assertEquals($mExpectedValue, $Database->{$sFunctionName}(...$aArguments[$sKey]));
            }
        }
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::getCharset()
     */
    public function testGetCharset()
    {
        $WpDatabase = $this->getWpDatabase(['get_var']);
        $WpDatabase->charset = 'testCharset';
        $WpDatabase->collate = 'testCollate';
        $WpDatabase->expects($this->once())
            ->method('get_var')
            ->with('SELECT VERSION() as mysql_version')
            ->will($this->returnValue('4.0.0'));
        $Wordpress = $this->getWrapperWithWpDatabase($WpDatabase);
        $Database = new Database($Wordpress);

        self::assertEquals('', $Database->getCharset());

        $WpDatabase = $this->getWpDatabase(['get_var']);
        $WpDatabase->expects($this->exactly(4))
            ->method('get_var')
            ->with('SELECT VERSION() as mysql_version')
            ->will($this->returnValue('4.1.0'));
        $Wordpress = $this->getWrapperWithWpDatabase($WpDatabase);
        $Database = new Database($Wordpress);

        self::assertEquals('', $Database->getCharset());

        $WpDatabase->charset = 'testCharset';
        $Wordpress = $this->getWrapperWithWpDatabase($WpDatabase);
        $Database = new Database($Wordpress);
        self::assertEquals('DEFAULT CHARACTER SET testCharset', $Database->getCharset());

        $WpDatabase->charset = 'testCharset';
        $WpDatabase->collate = 'testCollate';
        $Wordpress = $this->getWrapperWithWpDatabase($WpDatabase);
        $Database = new Database($Wordpress);
        self::assertEquals('DEFAULT CHARACTER SET testCharset COLLATE testCollate', $Database->getCharset());

        $WpDatabase->charset = null;
        $Wordpress = $this->getWrapperWithWpDatabase($WpDatabase);
        $Database = new Database($Wordpress);
        self::assertEquals(' COLLATE testCollate', $Database->getCharset());
    }
}
