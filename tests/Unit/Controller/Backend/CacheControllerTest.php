<?php
/**
 * CacheControllerTest.php
 *
 * The CacheControllerTest unit test class file.
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

use UserAccessManager\Controller\Backend\CacheController;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class CacheControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Backend
 * @coversDefaultClass \UserAccessManager\Controller\Backend\CacheController
 */
class CacheControllerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $cacheController = new CacheController(
            $this->getCache()
        );

        self::assertInstanceOf(CacheController::class, $cacheController);
    }

    /**
     * @group  unit
     * @covers ::invalidateTermCache()
     * @covers ::invalidatePostCache()
     */
    public function testInvalidateCache()
    {
        $cache = $this->getCache();
        $cache->expects($this->exactly(6))
            ->method('invalidate')
            ->withConsecutive(
                [ObjectMapHandler::POST_TERM_MAP_CACHE_KEY],
                [ObjectMapHandler::TERM_POST_MAP_CACHE_KEY],
                [ObjectMapHandler::TERM_TREE_MAP_CACHE_KEY],
                [ObjectMapHandler::TERM_POST_MAP_CACHE_KEY],
                [ObjectMapHandler::POST_TERM_MAP_CACHE_KEY],
                [ObjectMapHandler::POST_TREE_MAP_CACHE_KEY]
            );

        $objectController = new CacheController(
            $cache
        );

        $objectController->invalidateTermCache();
        $objectController->invalidatePostCache();
    }
}
