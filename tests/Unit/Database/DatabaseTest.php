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

namespace UserAccessManager\Tests\Unit\Database;

use PHPUnit\Framework\MockObject\MockObject;
use UserAccessManager\Database\Database;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\Wrapper\Wordpress;
use wpdb;

/**
 * Class DatabaseTest
 *
 * @package UserAccessManager\Tests\Unit\Database
 * @coversDefaultClass \UserAccessManager\Database\Database
 */
class DatabaseTest extends UserAccessManagerTestCase
{
    /**
     * @param array $methods
     * @return MockObject|wpdb
     */
    private function getWpDatabase(array $methods = [])
    {
        return $this->getMockBuilder(wpdb::class)
            ->setMethods($methods)
            ->getMock();
    }

    /**
     * @param MockObject $wpDatabase
     * @return MockObject|Wordpress
     */
    private function getWrapperWithWpDatabase(MockObject $wpDatabase)
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getDatabase')
            ->will($this->returnValue($wpDatabase));

        return $wordpress;
    }

    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $database = new Database($this->getWordpress());
        self::assertInstanceOf(Database::class, $database);
    }

    /**
     * @group  unit
     * @covers ::getWordpressDatabase()
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
     * @covers ::getUserGroupTable()
     */
    public function testGetUserGroupTable()
    {
        $simpleWpDatabase = $this->createMock(wpdb::class);
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
     * @covers ::getUserGroupToObjectTable()
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
     * @covers ::dbDelta()
     */
    public function testDbDelta()
    {
        $wpDatabase = $this->getWpDatabase();
        $wordpress = $this->getWrapperWithWpDatabase($wpDatabase);
        $wordpress->expects($this->exactly(2))
            ->method('dbDelta')
            ->withConsecutive(['', true], ['query', false])
            ->will($this->onConsecutiveCalls(['firstReturn'], ['secondReturn']));

        $database = new Database($wordpress);
        self::assertEquals(['firstReturn'], $database->dbDelta());
        self::assertEquals(['secondReturn'], $database->dbDelta('query', false));
    }

    /**
     * @group  unit
     * @covers ::getPrefix()
     * @covers ::getLastInsertId()
     * @covers ::getCurrentBlogId()
     * @covers ::getBlogsTable()
     * @covers ::getPostsTable()
     * @covers ::getTermRelationshipsTable()
     * @covers ::getTermTaxonomyTable()
     * @covers ::getUsersTable()
     * @covers ::getCapabilitiesTable()
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
     * @covers ::getColumn()
     * @covers ::getRow()
     * @covers ::getVariable()
     * @covers ::getBlogPrefix()
     * @covers ::prepare()
     * @covers ::query()
     * @covers ::getResults()
     * @covers ::insert()
     * @covers ::update()
     * @covers ::replace()
     * @covers ::delete()
     */
    public function testFunctionWrapper()
    {
        $wrapperFunctions = [
            'getColumn' => [
                'get_col',
                [[null, 0], ['query', 10]],
                [['firstReturn'], ['secondReturn']],
                [[], ['query', 10]]
            ],
            'getRow' => [
                'get_row',
                [[null, OBJECT, 0], ['query', 'testObject', 10]],
                [['firstReturn'], ['secondReturn']],
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
            'replace' => [
                'replace',
                [['table', ['a'], null], ['table', ['b'], 'format']],
                ['firstReturn', 'secondReturn'],
                [['table', ['a']], ['table', ['b'], 'format']]
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
     * @covers ::getCharset()
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
