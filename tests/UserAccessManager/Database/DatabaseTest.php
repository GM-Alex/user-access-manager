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
 * @version   SVN: $id$
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
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\wpdb
     */
    private function getWpDatabase(array $methods = [])
    {
        return $this->getMockBuilder('\wpdb')
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $wpDatabase
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Wordpress
     */
    private function getWrapperWithWpDatabase($wpDatabase)
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getDatabase')
            ->will($this->returnValue($wpDatabase));

        return $wordpress;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::__construct()
     */
    public function testCanCreateInstance()
    {
        $database = new Database($this->getWordpress());
        self::assertInstanceOf(Database::class, $database);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::getWordpressDatabase()
     */
    public function testGetWordpressDatabase()
    {
        $wpDatabase = $this->getWpDatabase();
        $wpDatabase->prefix = 'wp_';
        $wordpress = $this->getWrapperWithWpDatabase($wpDatabase);
        $database = new Database($wordpress);
        self::assertEquals($wpDatabase, $database->getWordpressDatabase());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::getUserGroupTable()
     */
    public function testGetUserGroupTable()
    {
        $simpleWpDatabase = new \stdClass();
        $simpleWpDatabase->prefix = 'wp_';

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getDatabase')
            ->will($this->returnValue($simpleWpDatabase));

        $database = new Database($wordpress);

        self::assertEquals('wp_uam_accessgroups', $database->getUserGroupTable());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::getUserGroupToObjectTable()
     */
    public function testGetUserGroupToObjectTable()
    {
        $wpDatabase = $this->getWpDatabase();
        $wpDatabase->prefix = 'wp_';
        $wordpress = $this->getWrapperWithWpDatabase($wpDatabase);
        $database = new Database($wordpress);

        self::assertEquals('wp_uam_accessgroup_to_object', $database->getUserGroupToObjectTable());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::dbDelta()
     */
    public function testDbDelta()
    {
        $wpDatabase = $this->getWpDatabase();
        $wordpress = $this->getWrapperWithWpDatabase($wpDatabase);
        $wordpress->expects($this->exactly(2))
            ->method('dbDelta')
            ->withConsecutive(['', true], ['query', false])
            ->will($this->onConsecutiveCalls('firstReturn', 'secondReturn'));

        $database = new Database($wordpress);
        self::assertEquals('firstReturn', $database->dbDelta());
        self::assertEquals('secondReturn', $database->dbDelta('query', false));
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
        $wpDatabase = $this->getWpDatabase();
        $wpDatabase->prefix = 'prefix_';
        $wpDatabase->insert_id = 'insert_id';
        $wpDatabase->blogid = 'blogid';
        $wpDatabase->blogs = 'blogs';
        $wpDatabase->posts = 'posts';
        $wpDatabase->term_relationships = 'term_relationships';
        $wpDatabase->term_taxonomy = 'term_taxonomy';
        $wpDatabase->users = 'users';

        $wordpress = $this->getWrapperWithWpDatabase($wpDatabase);
        $database = new Database($wordpress);

        self::assertEquals('prefix_', $database->getPrefix());
        self::assertEquals('insert_id', $database->getLastInsertId());
        self::assertEquals('blogid', $database->getCurrentBlogId());
        self::assertEquals('blogs', $database->getBlogsTable());
        self::assertEquals('posts', $database->getPostsTable());
        self::assertEquals('term_relationships', $database->getTermRelationshipsTable());
        self::assertEquals('term_taxonomy', $database->getTermTaxonomyTable());
        self::assertEquals('users', $database->getUsersTable());
        self::assertEquals('prefix_capabilities', $database->getCapabilitiesTable());
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
        $wrapperFunctions = [
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

        foreach ($wrapperFunctions as $functionName => $testValues) {
            $wrapperFunction = $testValues[0];
            $expectedArguments = $testValues[1];
            $expectedResults = $testValues[2];
            $arguments = $testValues[3];

            $wpDatabase = $this->getWpDatabase([$wrapperFunction]);
            $wpDatabase->expects($this->exactly(count($expectedResults)))
                ->method($wrapperFunction)
                ->withConsecutive(...$expectedArguments)
                ->will($this->onConsecutiveCalls(...$expectedResults));

            $wordpress = $this->getWrapperWithWpDatabase($wpDatabase);
            $database = new Database($wordpress);

            foreach ($expectedResults as $key => $expectedValue) {
                self::assertEquals($expectedValue, $database->{$functionName}(...$arguments[$key]));
            }
        }
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Database\Database::getCharset()
     */
    public function testGetCharset()
    {
        $wpDatabase = $this->getWpDatabase(['get_var']);
        $wpDatabase->charset = 'testCharset';
        $wpDatabase->collate = 'testCollate';
        $wpDatabase->expects($this->once())
            ->method('get_var')
            ->with('SELECT VERSION() as mysql_version')
            ->will($this->returnValue('4.0.0'));
        $wordpress = $this->getWrapperWithWpDatabase($wpDatabase);
        $database = new Database($wordpress);

        self::assertEquals('', $database->getCharset());

        $wpDatabase = $this->getWpDatabase(['get_var']);
        $wpDatabase->expects($this->exactly(4))
            ->method('get_var')
            ->with('SELECT VERSION() as mysql_version')
            ->will($this->returnValue('4.1.0'));
        $wordpress = $this->getWrapperWithWpDatabase($wpDatabase);
        $database = new Database($wordpress);

        self::assertEquals('', $database->getCharset());

        $wpDatabase->charset = 'testCharset';
        $wordpress = $this->getWrapperWithWpDatabase($wpDatabase);
        $database = new Database($wordpress);
        self::assertEquals('DEFAULT CHARACTER SET testCharset', $database->getCharset());

        $wpDatabase->charset = 'testCharset';
        $wpDatabase->collate = 'testCollate';
        $wordpress = $this->getWrapperWithWpDatabase($wpDatabase);
        $database = new Database($wordpress);
        self::assertEquals('DEFAULT CHARACTER SET testCharset COLLATE testCollate', $database->getCharset());

        $wpDatabase->charset = null;
        $wordpress = $this->getWrapperWithWpDatabase($wpDatabase);
        $database = new Database($wordpress);
        self::assertEquals(' COLLATE testCollate', $database->getCharset());
    }
}
