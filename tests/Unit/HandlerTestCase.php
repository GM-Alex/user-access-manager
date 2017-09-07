<?php
/**
 * HandlerTestCase.php
 *
 * The HandlerTestCase unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Unit;

use UserAccessManager\Object\ObjectHandler;

/**
 * Class HandlerTestCase
 *
 * @package UserAccessManager\Tests\Unit
 */
abstract class HandlerTestCase extends UserAccessManagerTestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Database\Database
     */
    protected function getDatabase()
    {
        $database = parent::getDatabase();

        $database->expects($this->any())
            ->method('getUserGroupTable')
            ->will($this->returnValue('getUserGroupTable'));

        $database->expects($this->any())
            ->method('getPrefix')
            ->will($this->returnValue('prefix_'));

        return $database;
    }

    /**
     * @param array $capabilities
     * @param int   $capExpects
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\WP_User
     */
    protected function getUser(array $capabilities = null, $capExpects = null)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass $user
         */
        $user = $this->getMockBuilder('\WP_User')
            ->setMethods(['has_cap'])
            ->getMock();
        $user->ID = 1;

        $capExpects = ($capExpects !== null) ? $this->exactly($capExpects) : $this->any();

        $user->expects($capExpects)
            ->method('has_cap')
            ->will($this->returnCallback(function ($cap) use ($capabilities) {
                return ($cap === 'user_cap' || in_array($cap, (array)$capabilities));
            }));

        if ($capabilities !== null) {
            $user->prefix_capabilities = $capabilities;
        }

        return $user;
    }

    /**
     * @param array $capabilities
     * @param int   $capExpects
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Wordpress
     */
    protected function getWordpressWithUser(array $capabilities = null, $capExpects = null)
    {
        $wordpress = $this->getWordpress();

        $user = $this->getUser($capabilities, $capExpects);
        $wordpress->expects($this->any())
            ->method('getCurrentUser')
            ->will($this->returnValue($user));

        return $wordpress;
    }

    /**
     * @param int $getPostsExpect
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectHandler
     */
    protected function getObjectHandler($getPostsExpect = null)
    {
        $objectHandler = parent::getObjectHandler();

        $objectHandler->expects($this->any())
            ->method('isValidObjectType')
            ->will($this->returnCallback(function ($objectType) {
                return ($objectType === 'objectType'
                    || $objectType === 'postType'
                    || $objectType === ObjectHandler::GENERAL_USER_OBJECT_TYPE);
            }));

        $objectHandler->expects($this->any())
            ->method('isPostType')
            ->will($this->returnCallback(function ($objectType) {
                return ($objectType === 'postType');
            }));

        $postExpects = ($getPostsExpect === null) ? $this->any() : $this->exactly($getPostsExpect);
        $objectHandler->expects($postExpects)
            ->method('getPost')
            ->will($this->returnCallback(function ($id) {
                if ($id === -1) {
                    return false;
                }

                /**
                 * @var \stdClass $post
                 */
                $post = $this->getMockBuilder('\WP_Post')->getMock();
                $post->ID = $id;
                $post->post_author = $id;
                return $post;
            }));

        return $objectHandler;
    }
}
