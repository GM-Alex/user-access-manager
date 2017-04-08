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
     * @param \PHPUnit_Framework_MockObject_MockObject $oWpDatabase
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Wordpress
     */
    private function getWrapperWithWpDatabase($oWpDatabase)
    {
        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->once())
            ->method('getDatabase')
            ->will($this->returnValue($oWpDatabase));

        return $oWordpress;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::__construct()
     */
    public function testCanCreateInstance()
    {
        $oDatabase = new Database($this->getWordpress());
        self::assertInstanceOf('\UserAccessManager\Database\Database', $oDatabase);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::getUserGroupTable()
     */
    public function testGetUserGroupTable()
    {
        $oSimpleWpDatabase = new \stdClass();
        $oSimpleWpDatabase->prefix = 'wp_';

        $oWordpress = $this->getWordpress();
        $oWordpress->expects($this->once())
            ->method('getDatabase')
            ->will($this->returnValue($oSimpleWpDatabase));

        $oDatabase = new Database($oWordpress);

        self::assertEquals('wp_uam_accessgroups', $oDatabase->getUserGroupTable());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::getUserGroupToObjectTable()
     */
    public function testGetUserGroupToObjectTable()
    {
        $oWpDatabase = $this->getWpDatabase();
        $oWpDatabase->prefix = 'wp_';
        $oWordpress = $this->getWrapperWithWpDatabase($oWpDatabase);
        $oDatabase = new Database($oWordpress);

        self::assertEquals('wp_uam_accessgroup_to_object', $oDatabase->getUserGroupToObjectTable());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::dbDelta()
     */
    public function testDbDelta()
    {
        $oWpDatabase = $this->getWpDatabase();
        $oWordpress = $this->getWrapperWithWpDatabase($oWpDatabase);
        $oWordpress->expects($this->exactly(2))
            ->method('dbDelta')
            ->withConsecutive(['', true], ['query', false])
            ->will($this->onConsecutiveCalls('firstReturn', 'secondReturn'));

        $oDatabase = new Database($oWordpress);
        self::assertEquals('firstReturn', $oDatabase->dbDelta());
        self::assertEquals('secondReturn', $oDatabase->dbDelta('query', false));
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
        $oWpDatabase = $this->getWpDatabase();
        $oWpDatabase->prefix = 'prefix_';
        $oWpDatabase->insert_id = 'insert_id';
        $oWpDatabase->blogid = 'blogid';
        $oWpDatabase->blogs = 'blogs';
        $oWpDatabase->posts = 'posts';
        $oWpDatabase->term_relationships = 'term_relationships';
        $oWpDatabase->term_taxonomy = 'term_taxonomy';
        $oWpDatabase->users = 'users';

        $oWordpress = $this->getWrapperWithWpDatabase($oWpDatabase);
        $oDatabase = new Database($oWordpress);

        self::assertEquals('prefix_', $oDatabase->getPrefix());
        self::assertEquals('insert_id', $oDatabase->getLastInsertId());
        self::assertEquals('blogid', $oDatabase->getCurrentBlogId());
        self::assertEquals('blogs', $oDatabase->getBlogsTable());
        self::assertEquals('posts', $oDatabase->getPostsTable());
        self::assertEquals('term_relationships', $oDatabase->getTermRelationshipsTable());
        self::assertEquals('term_taxonomy', $oDatabase->getTermTaxonomyTable());
        self::assertEquals('users', $oDatabase->getUsersTable());
        self::assertEquals('prefix_capabilities', $oDatabase->getCapabilitiesTable());
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

            $oWpDatabase = $this->getWpDatabase([$sWrapperFunction]);
            $oWpDatabase->expects($this->exactly(count($aExpectedResults)))
                ->method($sWrapperFunction)
                ->withConsecutive(...$aExpectedArguments)
                ->will($this->onConsecutiveCalls(...$aExpectedResults));

            $oWordpress = $this->getWrapperWithWpDatabase($oWpDatabase);
            $oDatabase = new Database($oWordpress);

            foreach ($aExpectedResults as $sKey => $mExpectedValue) {
                self::assertEquals($mExpectedValue, $oDatabase->{$sFunctionName}(...$aArguments[$sKey]));
            }
        }
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::getCharset()
     */
    public function testGetCharset()
    {
        $oWpDatabase = $this->getWpDatabase(['get_var']);
        $oWpDatabase->charset = 'testCharset';
        $oWpDatabase->collate = 'testCollate';
        $oWpDatabase->expects($this->once())
            ->method('get_var')
            ->with('SELECT VERSION() as mysql_version')
            ->will($this->returnValue('4.0.0'));
        $oWordpress = $this->getWrapperWithWpDatabase($oWpDatabase);
        $oDatabase = new Database($oWordpress);

        self::assertEquals('', $oDatabase->getCharset());

        $oWpDatabase = $this->getWpDatabase(['get_var']);
        $oWpDatabase->expects($this->exactly(4))
            ->method('get_var')
            ->with('SELECT VERSION() as mysql_version')
            ->will($this->returnValue('4.1.0'));
        $oWordpress = $this->getWrapperWithWpDatabase($oWpDatabase);
        $oDatabase = new Database($oWordpress);

        self::assertEquals('', $oDatabase->getCharset());

        $oWpDatabase->charset = 'testCharset';
        $oWordpress = $this->getWrapperWithWpDatabase($oWpDatabase);
        $oDatabase = new Database($oWordpress);
        self::assertEquals('DEFAULT CHARACTER SET testCharset', $oDatabase->getCharset());

        $oWpDatabase->charset = 'testCharset';
        $oWpDatabase->collate = 'testCollate';
        $oWordpress = $this->getWrapperWithWpDatabase($oWpDatabase);
        $oDatabase = new Database($oWordpress);
        self::assertEquals('DEFAULT CHARACTER SET testCharset COLLATE testCollate', $oDatabase->getCharset());

        $oWpDatabase->charset = null;
        $oWordpress = $this->getWrapperWithWpDatabase($oWpDatabase);
        $oDatabase = new Database($oWordpress);
        self::assertEquals(' COLLATE testCollate', $oDatabase->getCharset());
    }
}
