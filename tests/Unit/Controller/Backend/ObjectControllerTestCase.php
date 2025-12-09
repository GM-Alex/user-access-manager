<?php
/**
 * ObjectControllerTestCase.php
 *
 * The ObjectControllerTestCase class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Controller\Backend;

use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use stdClass;
use UserAccessManager\Controller\Backend\ObjectController;
use UserAccessManager\Controller\Backend\ObjectInformation;
use UserAccessManager\Controller\Backend\ObjectInformationFactory;
use UserAccessManager\Controller\Backend\PostObjectController;
use UserAccessManager\Controller\Backend\TermObjectController;
use UserAccessManager\Controller\Backend\UserObjectController;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class ObjectControllerTestCase
 *
 * @package UserAccessManager\Tests\Unit\Controller\Backend
 */
abstract class ObjectControllerTestCase extends UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private FileSystem $root;

    /**
     * Setup virtual file system.
     */
    protected function setUp(): void
    {
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();
    }

    /**
     * Tear down virtual file system.
     */
    protected function tearDown(): void
    {
        $this->root->unmount();
    }

    /**
     * @return MockObject|ObjectInformation
     */
    protected function getObjectInformation(): MockObject|ObjectInformation
    {
        $objectInformation = $this->createMock(ObjectInformation::class);

        $objectType = null;
        $objectId = null;
        $userGroupDiff = 0;
        $objectUserGroups = [];

        $objectInformation->expects($this->any())
            ->method('getObjectType')
            ->will($this->returnCallback(function () use (&$objectType) {
                return $objectType;
            }));

        $objectInformation->expects($this->any())
            ->method('setObjectType')
            ->will($this->returnCallback(function ($newObjectType) use (&$objectType, &$objectInformation) {
                $objectType = $newObjectType;
                return $objectInformation;
            }));

        $objectInformation->expects($this->any())
            ->method('getObjectId')
            ->will($this->returnCallback(function () use (&$objectId) {
                return $objectId;
            }));

        $objectInformation->expects($this->any())
            ->method('setObjectId')
            ->will($this->returnCallback(function ($newObjectId) use (&$objectId, &$objectInformation) {
                $objectId = $newObjectId;
                return $objectInformation;
            }));

        $objectInformation->expects($this->any())
            ->method('getUserGroupDiff')
            ->will($this->returnCallback(function () use (&$userGroupDiff) {
                return $userGroupDiff;
            }));

        $objectInformation->expects($this->any())
            ->method('setUserGroupDiff')
            ->will($this->returnCallback(function ($newUserGroupDiff) use (&$userGroupDiff, &$objectInformation) {
                $userGroupDiff = $newUserGroupDiff;
                return $objectInformation;
            }));

        $objectInformation->expects($this->any())
            ->method('getObjectUserGroups')
            ->will($this->returnCallback(function () use (&$objectUserGroups) {
                return $objectUserGroups;
            }));

        $objectInformation->expects($this->any())
            ->method('setObjectUserGroups')
            ->will($this->returnCallback(function ($newObjectUserGroups) use (&$objectUserGroups, &$objectInformation) {
                $objectUserGroups = $newObjectUserGroups;
                return $objectInformation;
            }));

        return $objectInformation;
    }

    /**
     * @return MockObject|ObjectInformationFactory
     */
    protected function getObjectInformationFactory(): ObjectInformationFactory|MockObject
    {
        $objectInformationFactory = parent::getObjectInformationFactory();
        $objectInformationFactory->expects($this->once())
            ->method('createObjectInformation')
            ->will($this->returnValue($this->getObjectInformation()));

        return $objectInformationFactory;
    }

    /**
     * @param int $id
     * @param string $displayName
     * @param string $userLogin
     * @return MockObject|stdClass
     */
    protected function getUser(int $id, string $displayName, string $userLogin): MockObject|stdClass
    {
        /**
         * @var MockObject|stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->ID = $id;
        $user->display_name = $displayName;
        $user->user_login = $userLogin;

        return $user;
    }

    /**
     * @return MockObject|ObjectHandler
     */
    protected function getExtendedObjectHandler(): ObjectHandler|MockObject
    {
        /**
         * @var MockObject|stdClass $post
         */
        $post = $this->getMockBuilder('\WP_Post')->getMock();
        $post->ID = 1;
        $post->post_type = 'post';

        /**
         * @var MockObject|stdClass $revisionPost
         */
        $revisionPost = $this->getMockBuilder('\WP_Post')->getMock();
        $revisionPost->ID = 2;
        $revisionPost->post_type = 'revision';
        $revisionPost->post_parent = 1;

        /**
         * @var MockObject|stdClass $attachment
         */
        $attachment = $this->getMockBuilder('\WP_Post')->getMock();
        $attachment->ID = 3;
        $attachment->post_type = 'attachment';

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->any())
            ->method('getPost')
            ->will($this->returnCallback(function ($postId) use ($post, $revisionPost, $attachment) {
                if ($postId === 1) {
                    return $post;
                } elseif ($postId === 2) {
                    return $revisionPost;
                } elseif ($postId === 3) {
                    return $attachment;
                }

                return false;
            }));

        $objectHandler->expects($this->any())
            ->method('getTerm')
            ->will($this->returnCallback(function ($termId) {
                if ($termId === 0) {
                    return false;
                }

                /**
                 * @var MockObject|stdClass $term
                 */
                $term = $this->getMockBuilder('\WP_Term')->getMock();
                $term->term_id = $termId;
                $term->taxonomy = 'taxonomy_' . $termId;

                return $term;
            }));

        return $objectHandler;
    }

    /**
     * @param string $class
     * @param array $expectedFilteredUserGroupsForObject
     * @return mixed
     */
    protected function getTestSaveObjectDataPrototype(string $class, array $expectedFilteredUserGroupsForObject): mixed
    {
        $userHandler = $this->getUserHandler();
        $userHandler->expects($this->any())
            ->method('checkUserAccess')
            ->with('manage_user_groups')
            ->will($this->returnValue(true));

        $userGroupHandler = $this->getUserGroupHandler();
        $userGroupHandler->expects($this->any())
            ->method('getFilteredUserGroups')
            ->will($this->returnValue([]));

        $userGroupHandler->expects($this->exactly(count($expectedFilteredUserGroupsForObject)))
            ->method('getFilteredUserGroupsForObject')
            ->withConsecutive(...$expectedFilteredUserGroupsForObject)
            ->will($this->returnValue([]));

        $mainConfigClasses = [
            UserObjectController::class,
            TermObjectController::class,
            PostObjectController::class,
            ObjectController::class
        ];

        if (in_array($class, $mainConfigClasses) === true) {
            $objectController = new $class(
                $this->getPhp(),
                $this->getWordpress(),
                $this->getWordpressConfig(),
                $this->getMainConfig(),
                $this->getDatabase(),
                $this->getDateUtil(),
                $this->getExtendedObjectHandler(),
                $userHandler,
                $userGroupHandler,
                $this->getUserGroupAssignmentHandler(),
                $this->getAccessHandler(),
                $this->getObjectInformationFactory()
            );
        } else {
            $objectController = new $class(
                $this->getPhp(),
                $this->getWordpress(),
                $this->getWordpressConfig(),
                $this->getDatabase(),
                $this->getCache(),
                $this->getExtendedObjectHandler(),
                $userHandler,
                $userGroupHandler,
                $this->getUserGroupAssignmentHandler(),
                $this->getAccessHandler(),
                $this->getObjectInformationFactory()
            );
        }

        $_POST[ObjectController::UPDATE_GROUPS_FORM_NAME] = 1;

        return $objectController;
    }

    /**
     * @param string $class
     * @param array $requestedFiles
     * @param array $groupsForObject
     * @return mixed
     */
    protected function getTestEditFormPrototype(string $class, array $requestedFiles, array $groupsForObject): mixed
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('root', new Directory([
            'src' => new Directory([
                'View' => new Directory([
                    'ObjectColumn.php' => new File('<?php echo \'ObjectColumn\';'),
                    'UserColumn.php' => new File('<?php echo \'UserColumn\';'),
                    'PostEditForm.php' => new File('<?php echo \'PostEditForm\';'),
                    'BulkEditForm.php' => new File('<?php echo \'BulkEditForm\';'),
                    'MediaAjaxEditForm.php' => new File('<?php echo \'MediaAjaxEditForm\';'),
                    'UserProfileEditForm.php' => new File('<?php echo \'UserProfileEditForm\';'),
                    'TermEditForm.php' => new File('<?php echo \'TermEditForm\';'),
                    'GroupSelectionForm.php' => new File('<?php echo \'GroupSelectionForm\';')
                ])
            ])
        ]));

        $php = $this->getPhp();

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->exactly(count($requestedFiles)))
            ->method('getRealPath')
            ->will($this->returnValue('vfs://root/'));

        $userGroupHandler = $this->getUserGroupHandler();

        $userGroupHandler->expects($this->exactly(count($groupsForObject)))
            ->method('getUserGroupsForObject')
            ->withConsecutive(...$groupsForObject)
            ->will($this->returnValue([]));

        $userGroupHandler->expects($this->exactly(count($groupsForObject)))
            ->method('getFilteredUserGroupsForObject')
            ->withConsecutive(...$groupsForObject)
            ->will($this->returnValue([]));

        $objectController = new $class(
            $php,
            $this->getWordpress(),
            $wordpressConfig,
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getExtendedObjectHandler(),
            $this->getUserHandler(),
            $userGroupHandler,
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        $requestedFilesParam = [];

        foreach ($requestedFiles as $requestedFile) {
            $requestedFilesParam[] = [$objectController, $requestedFile];
        }

        $php->expects($this->exactly(count($requestedFiles)))
            ->method('includeFile')
            ->withConsecutive(...$requestedFilesParam)
            ->will($this->returnCallback(function (ObjectController $controller, $file) {
                echo '!' . get_class($controller) . '|' . $file . '|' . $controller->getGroupsFormName() . '!';
            }));

        return $objectController;
    }

    /**
     * @param ObjectController $objectController
     * @throws ReflectionException
     */
    protected function resetControllerObjectInformation(ObjectController $objectController): void
    {
        self::setValue($objectController, 'objectInformation', $this->getObjectInformation());
    }

    /**
     * @param string $class
     * @param string $id
     * @param string $type
     * @return mixed
     */
    protected function getTestRemoveObjectDataPrototype(string $class, string $id, string $type): mixed
    {
        $database = $this->getDatabase();
        $database->expects($this->once())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->once())
            ->method('delete')
            ->with(
                'userGroupToObjectTable',
                ['object_id' => $id, 'object_type' => $type],
                ['%d', '%s']
            );

        return new $class(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $database,
            $this->getDateUtil(),
            $this->getExtendedObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );
    }
}
