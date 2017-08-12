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
namespace UserAccessManager\Tests\Controller\Backend;

use UserAccessManager\Controller\Backend\ObjectController;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\Tests\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class ObjectControllerTestCase
 *
 * @package UserAccessManager\Tests\Controller\Backend
 */
abstract class ObjectControllerTestCase extends UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private $root;

    /**
     * Setup virtual file system.
     */
    public function setUp()
    {
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();
    }

    /**
     * Tear down virtual file system.
     */
    public function tearDown()
    {
        $this->root->unmount();
    }

    /**
     * @param int    $id
     * @param string $displayName
     * @param string $userLogin
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\stdClass
     */
    protected function getUser($id, $displayName, $userLogin)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')->getMock();
        $user->ID = $id;
        $user->display_name = $displayName;
        $user->user_login = $userLogin;

        return $user;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectHandler
     */
    protected function getExtendedObjectHandler()
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $post
         */
        $post = $this->getMockBuilder('\WP_Post')->getMock();
        $post->ID = 1;
        $post->post_type = 'post';

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $revisionPost
         */
        $revisionPost = $this->getMockBuilder('\WP_Post')->getMock();
        $revisionPost->ID = 2;
        $revisionPost->post_type = 'revision';
        $revisionPost->post_parent = 1;

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $attachment
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
                 * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $term
                 */
                $term = $this->getMockBuilder('\WP_Term')->getMock();
                $term->term_id = $termId;
                $term->taxonomy = 'taxonomy_'.$termId;

                return $term;
            }));

        return $objectHandler;
    }

    protected function getTestSaveObjectDataPrototype($class, array $requestedFiles, array $groupsForObject)
    {
        //TODO
    }

    /**
     * @param string $class
     * @param array  $requestedFiles
     * @param array  $groupsForObject
     *
     * @return mixed
     */
    protected function getTestEditFormPrototype($class, array $requestedFiles, array $groupsForObject)
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('src', new Directory([
            'View'  => new Directory([
                'ObjectColumn.php' => new File('<?php echo \'ObjectColumn\';'),
                'UserColumn.php' => new File('<?php echo \'UserColumn\';'),
                'PostEditForm.php' => new File('<?php echo \'PostEditForm\';'),
                'BulkEditForm.php' => new File('<?php echo \'BulkEditForm\';'),
                'MediaAjaxEditForm.php' => new File('<?php echo \'MediaAjaxEditForm\';'),
                'UserProfileEditForm.php' => new File('<?php echo \'UserProfileEditForm\';'),
                'TermEditForm.php' => new File('<?php echo \'TermEditForm\';'),
                'GroupSelectionForm.php' => new File('<?php echo \'GroupSelectionForm\';')
            ])
        ]));

        $php = $this->getPhp();

        $config = $this->getMainConfig();
        $config->expects($this->exactly(count($requestedFiles)))
            ->method('getRealPath')
            ->will($this->returnValue('vfs:/'));

        $accessHandler = $this->getAccessHandler();

        $accessHandler->expects($this->exactly(count($groupsForObject)))
            ->method('getUserGroupsForObject')
            ->withConsecutive(...$groupsForObject)
            ->will($this->returnValue([]));

        $accessHandler->expects($this->exactly(count($groupsForObject)))
            ->method('getFilteredUserGroupsForObject')
            ->withConsecutive(...$groupsForObject)
            ->will($this->returnValue([]));

        $objectController = new $class(
            $php,
            $this->getWordpress(),
            $config,
            $this->getDatabase(),
            $this->getCache(),
            $this->getExtendedObjectHandler(),
            $accessHandler,
            $this->getUserGroupFactory()
        );

        $requestedFilesParam = [];

        foreach ($requestedFiles as $requestedFile) {
            $requestedFilesParam[] = [$objectController, $requestedFile];
        }

        $php->expects($this->exactly(count($requestedFiles)))
            ->method('includeFile')
            ->withConsecutive(...$requestedFilesParam)
            ->will($this->returnCallback(function (ObjectController $controller, $file) {
                echo '!'.get_class($controller).'|'.$file.'|'.$controller->getGroupsFormName().'!';
            }));

        return $objectController;
    }

    /**
     * @param string $class
     * @param string $id
     * @param string $type
     *
     * @return mixed
     */
    protected function getTestRemoveObjectDataPrototype($class, $id, $type)
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
            $this->getMainConfig(),
            $database,
            $this->getCache(),
            $this->getExtendedObjectHandler(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );
    }
}
