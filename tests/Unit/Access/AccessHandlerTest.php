<?php
/**
 * AccessHandlerTest.php
 *
 * The AccessHandlerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Access;

use ReflectionException;
use stdClass;
use UserAccessManager\Access\AccessHandler;
use UserAccessManager\Tests\StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Tests\Unit\HandlerTestCase;
use UserAccessManager\UserGroup\UserGroupTypeException;

/**
 * Class AccessHandlerTest
 *
 * @package UserAccessManager\Tests\Unit\Access
 * @coversDefaultClass \UserAccessManager\Access\AccessHandler
 */
class AccessHandlerTest extends HandlerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler()
        );

        self::assertInstanceOf(AccessHandler::class, $accessHandler);
    }

    /**
     * @group  unit
     * @covers ::checkObjectAccess()
     * @covers ::isAdmin()
     * @covers ::hasAuthorAccess()
     * @covers ::getUserUserGroupsForObjectAccess()
     * @throws UserGroupTypeException
     * @throws ReflectionException
     */
    public function testCheckObjectAccess()
    {
        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->once())
            ->method('checkUserAccess')
            ->will($this->returnValue(true));

        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(0),
            $userHandler,
            $this->getUserGroupHandler()
        );

        self::assertTrue($accessHandler->checkObjectAccess('invalid', 1));
        self::assertTrue($accessHandler->checkObjectAccess('postType', 2));

        $wordpress = $this->getWordpressWithUser(null, null, 2);

        $tags = [];
        $isAdmins = [];

        $wordpress->expects($this->exactly(5))
            ->method('applyFilters')
            ->will($this->returnCallback(function ($tag, $userGroups, $isAdmin) use (&$tags, &$isAdmins) {
                $tags[] = $tag;
                $isAdmins[] = $isAdmin;
                return $userGroups;
            }));

        $wordpress->expects($this->exactly(7))
            ->method('isAdmin')
            ->will($this->onConsecutiveCalls(
                false,
                false,
                false,
                false,
                true,
                true,
                true
            ));

        $wordpress->expects($this->exactly(2))
            ->method('isUserMemberOfBlog')
            ->will($this->returnValue(true));

        $mainConfig = $this->getMainConfig();

        $mainConfig->expects($this->exactly(7))
            ->method('authorsHasAccessToOwn')
            ->will($this->onConsecutiveCalls(true, true, false, false, true, false, false));

        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->exactly(7))
            ->method('checkUserAccess')
            ->will($this->returnValue(false));

        $userGroupHandler = $this->getUserGroupHandler();

        $objectUserGroups = [
            'postType' => [
                -1 => [3 => $this->getUserGroup(11)],
                1 => [3 => $this->getUserGroup(3)],
                2 => [0 => $this->getUserGroup(0)],
                3 => [],
                4 => [10 => $this->getUserGroup(10)],
            ]
        ];

        $userGroupHandler->expects($this->exactly(6))
            ->method('getUserGroupsForObject')
            ->will($this->returnCallback(function ($objectType, $objectId) use ($objectUserGroups) {
                return $objectUserGroups[$objectType][$objectId];
            }));

        $firstUserUserGroups = [0 => $this->getUserGroup(0, true, false, [''])];
        $secondUserGroups = [3 => $this->getUserGroup(3, true, false, [''])];
        $thirdUserGroups = [3 => $this->getUserGroup(3, true, false, [''], 'none', 'all')];

        $userGroupHandler->expects($this->exactly(5))
            ->method('getUserGroupsForUser')
            ->will($this->onConsecutiveCalls(
                $firstUserUserGroups,
                $firstUserUserGroups,
                $firstUserUserGroups,
                $secondUserGroups,
                $thirdUserGroups
            ));

        $accessHandler = new AccessHandler(
            $wordpress,
            $mainConfig,
            $this->getDatabase(),
            $this->getObjectHandler(3, 7),
            $userHandler,
            $userGroupHandler
        );

        self::assertTrue($accessHandler->checkObjectAccess('postType', 1, false));
        self::assertTrue($accessHandler->checkObjectAccess('postType', 2));
        self::assertTrue($accessHandler->checkObjectAccess('postType', 3));
        self::assertFalse($accessHandler->checkObjectAccess('postType', 4));
        self::assertFalse($accessHandler->checkObjectAccess('postType', -1));

        self::setValue($accessHandler, 'objectAccess', []);
        self::assertFalse($accessHandler->checkObjectAccess('postType', 1));

        self::setValue($accessHandler, 'objectAccess', []);
        self::assertTrue($accessHandler->checkObjectAccess('postType', 1));
        self::assertTrue($accessHandler->checkObjectAccess('postType', 1));

        self::assertEquals(
            [
                'uam_get_user_user_groups_for_object_access',
                'uam_get_user_user_groups_for_object_access',
                'uam_get_user_user_groups_for_object_access',
                'uam_get_user_user_groups_for_object_access',
                'uam_get_user_user_groups_for_object_access'
            ],
            $tags
        );
        self::assertEquals([false, false, false, true, true], $isAdmins);
    }

    /**
     * @group  unit
     * @covers ::getExcludedTerms()
     * @covers ::getExcludedObjects()
     * @throws UserGroupTypeException
     */
    public function testGetExcludedTerms()
    {
        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->once())
            ->method('checkUserAccess')
            ->will($this->returnValue(true));

        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(['manage_user_groups']),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $userHandler,
            $this->getUserGroupHandler()
        );

        self::assertEquals([], $accessHandler->getExcludedTerms());

        $userGroupHandler = $this->getUserGroupHandler();

        $userGroups = [
            0 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [], [1 => 'term', 2 => 'term', 5 => 'term']),
            1 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [], [3 => 'term', 2 => 'term', 4 => 'term'])
        ];

        $userGroupHandler->expects($this->once())
            ->method('getFullUserGroups')
            ->will($this->returnValue($userGroups));

        $userGroupsForUser = [
            3 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [], [1 => 'term', 3 => 'term']),
            4 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [], [5 => 'term', 3 => 'term'])
        ];

        $userGroupHandler->expects($this->once())
            ->method('getUserGroupsForUser')
            ->will($this->returnValue($userGroupsForUser));

        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $userGroupHandler
        );

        self::assertEquals([2 => 2, 4 => 4], $accessHandler->getExcludedTerms());
    }

    /**
     * @group  unit
     * @covers ::getExcludedPosts()
     * @covers ::getNoneHiddenPostTypes()
     * @covers ::getExcludedObjects()
     * @throws UserGroupTypeException
     * @throws ReflectionException
     */
    public function testGetExcludedPosts()
    {
        $userHandler = $this->getUserHandler();

        $userHandler->expects($this->once())
            ->method('checkUserAccess')
            ->will($this->returnValue(true));

        $accessHandler = new AccessHandler(
            $this->getWordpressWithUser(['manage_user_groups']),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $userHandler,
            $this->getUserGroupHandler()
        );

        self::assertEquals([], $accessHandler->getExcludedPosts());

        $objectHandler = $this->getObjectHandler();

        $wordpress = $this->getWordpressWithUser();

        $wordpress->expects($this->exactly(3))
            ->method('isAdmin')
            ->will($this->onConsecutiveCalls(false, true, false));

        $config = $this->getMainConfig();

        $config->expects($this->exactly(4))
            ->method('authorsHasAccessToOwn')
            ->will($this->onConsecutiveCalls(false, false, true, true));


        $config->expects($this->exactly(4))
            ->method('hidePostType')
            ->withConsecutive(['post'], ['page'], ['post'], ['page'])
            ->will($this->onConsecutiveCalls(true, false, true, false));

        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getPostsTable')
            ->will($this->returnValue('postTable'));

        $database->expects($this->exactly(2))
            ->method('prepare')
            ->with(
                new MatchIgnoreWhitespace(
                    "SELECT ID
                    FROM postTable
                    WHERE post_author = %d"
                ),
                1
            )
            ->will($this->onConsecutiveCalls('ownPostQuery', 'invalid'));

        $database->expects($this->exactly(2))
            ->method('getResults')
            ->withConsecutive(['ownPostQuery'], ['invalid'])
            ->will($this->returnCallback(function ($query) {
                if ($query === 'invalid') {
                    return '';
                }

                $post = new stdClass();
                $post->ID = 4;

                // Don't return an array to check type cast
                return [$post];
            }));

        $objectHandler->expects($this->exactly(2))
            ->method('getPostTypes')
            ->will($this->returnValue(['post' => 'post', 'page' => 'page']));

        $userGroupHandler = $this->getUserGroupHandler();

        $userGroups = [
            0 => $this->getUserGroup(
                0,
                true,
                false,
                [''],
                'none',
                'none',
                [1 => 'post', 2 => 'page', 5 => 'post', 6 => 'post']
            ),
            1 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [3 => 'post', 2 => 'page', 4 => 'post'])
        ];

        $userGroupHandler->expects($this->exactly(4))
            ->method('getFullUserGroups')
            ->will($this->returnValue($userGroups));

        $userGroupsForUser = [
            3 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [1 => 'post', 3 => 'post']),
            4 => $this->getUserGroup(0, true, false, [''], 'none', 'none', [5 => 'post', 3 => 'post'])
        ];

        $userGroupHandler->expects($this->exactly(4))
            ->method('getUserGroupsForUser')
            ->will($this->returnValue($userGroupsForUser));

        $accessHandler = new AccessHandler(
            $wordpress,
            $config,
            $database,
            $objectHandler,
            $this->getUserHandler(),
            $userGroupHandler
        );

        self::assertEquals([4 => 4, 6 => 6], $accessHandler->getExcludedPosts());

        self::setValue($accessHandler, 'noneHiddenPostTypes', null);
        self::setValue($accessHandler, 'excludedPosts', null);
        self::assertEquals([2 => 2, 4 => 4, 6 => 6], $accessHandler->getExcludedPosts());

        self::setValue($accessHandler, 'noneHiddenPostTypes', null);
        self::setValue($accessHandler, 'excludedPosts', null);
        self::assertEquals([6 => 6], $accessHandler->getExcludedPosts());
        self::setValue($accessHandler, 'excludedPosts', null);
        self::assertEquals([4 => 4, 6 => 6], $accessHandler->getExcludedPosts());
    }
}
