<?php
/**
 * TermObjectControllerTest.php
 *
 * The TermObjectControllerTest class file.
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
use UserAccessManager\Controller\Backend\TermObjectController;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
use WP_Term;

/**
 * Class TermObjectControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Backend
 * @coversDefaultClass \UserAccessManager\Controller\Backend\TermObjectController
 */
class TermObjectControllerTest extends ObjectControllerTestCase
{
    /**
     * @group unit
     */
    public function testCanCreateInstance()
    {
        $termObjectController = new TermObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        self::assertInstanceOf(TermObjectController::class, $termObjectController);
    }

    /**
     * @group  unit
     * @covers ::addTermColumnsHeader()
     */
    public function testAddTermColumnsHeader()
    {
        $termObjectController = new TermObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        self::assertEquals(
            ['b' => 'b', ObjectController::COLUMN_NAME => TXT_UAM_COLUMN_ACCESS],
            $termObjectController->addTermColumnsHeader(['b' => 'b'])
        );
    }

    /**
     * @group  unit
     * @covers ::addTermColumn()
     * @throws UserGroupTypeException
     */
    public function testAddTermColumn()
    {
        $termObjectController = new TermObjectController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getDatabase(),
            $this->getDateUtil(),
            $this->getExtendedObjectHandler(),
            $this->getUserHandler(),
            $this->getUserGroupHandler(),
            $this->getUserGroupAssignmentHandler(),
            $this->getAccessHandler(),
            $this->getObjectInformationFactory()
        );

        $termObjectController->addTermColumn('return', ObjectController::COLUMN_NAME, 1);
    }

    /**
     * @group  unit
     * @covers ::addTermColumn()
     * @covers ::showTermEditForm()
     * @throws UserGroupTypeException
     * @throws ReflectionException
     */
    public function testEditForm()
    {
        /**
         * @var TermObjectController $termObjectController
         */
        $termObjectController = $this->getTestEditFormPrototype(
            TermObjectController::class,
            [
                'vfs://root/src/View/ObjectColumn.php',
                'vfs://root/src/View/ObjectColumn.php',
                'vfs://root/src/View/TermEditForm.php',
                'vfs://root/src/View/TermEditForm.php'
            ],
            [
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 0],
                ['taxonomy_1', 1],
                ['category', 5]
            ]
        );

        self::assertEquals('content', $termObjectController->addTermColumn('content', 'invalid', 1));
        $this->resetControllerObjectInformation($termObjectController);

        self::assertEquals('content', $termObjectController->addTermColumn('content', 'invalid', 1));
        $this->resetControllerObjectInformation($termObjectController);

        $expected = 'content!UserAccessManager\Controller\Backend\TermObjectController|'
            . 'vfs://root/src/View/ObjectColumn.php|uam_user_groups!';

        self::assertEquals(
            $expected,
            $termObjectController->addTermColumn('content', ObjectController::COLUMN_NAME, 0)
        );
        self::assertEquals(
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
            $termObjectController->getObjectInformation()->getObjectType()
        );
        self::assertEquals(0, $termObjectController->getObjectInformation()->getObjectId());
        $this->resetControllerObjectInformation($termObjectController);

        self::assertEquals(
            $expected,
            $termObjectController->addTermColumn('content', ObjectController::COLUMN_NAME, 1)
        );
        self::assertEquals('taxonomy_1', $termObjectController->getObjectInformation()->getObjectType());
        self::assertEquals(1, $termObjectController->getObjectInformation()->getObjectId());
        $this->resetControllerObjectInformation($termObjectController);

        $termObjectController->showTermEditForm('category');
        self::assertEquals('category', $termObjectController->getObjectInformation()->getObjectType());
        self::assertEquals(null, $termObjectController->getObjectInformation()->getObjectId());
        $expectedOutput = '!UserAccessManager\Controller\Backend\TermObjectController|'
            . 'vfs://root/src/View/TermEditForm.php|uam_user_groups!';
        $this->resetControllerObjectInformation($termObjectController);

        /**
         * @var MockObject|stdClass|WP_Term $term
         */
        $term = $this->getMockBuilder('\WP_Term')->getMock();
        $term->term_id = 5;
        $term->taxonomy = 'category';
        $termObjectController->showTermEditForm($term);
        self::assertEquals('category', $termObjectController->getObjectInformation()->getObjectType());
        self::assertEquals(5, $termObjectController->getObjectInformation()->getObjectId());
        $expectedOutput .= '!UserAccessManager\Controller\Backend\TermObjectController|'
            . 'vfs://root/src/View/TermEditForm.php|uam_user_groups!';
        $this->resetControllerObjectInformation($termObjectController);

        self::expectOutputString($expectedOutput);
    }

    /**
     * @group  unit
     * @covers ::saveTermData()
     * @throws UserGroupTypeException
     */
    public function testSaveUserData()
    {
        /**
         * @var TermObjectController $termObjectController
         */
        $termObjectController = $this->getTestSaveObjectDataPrototype(
            TermObjectController::class,
            [
                [ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 0],
                ['taxonomy_1', 1]
            ]
        );

        $termObjectController->saveTermData(0);
        $termObjectController->saveTermData(1);
    }

    /**
     * @group  unit
     * @covers ::removeTermData()
     */
    public function testRemoveTermData()
    {
        /**
         * @var TermObjectController $termObjectController
         */
        $termObjectController = $this->getTestRemoveObjectDataPrototype(
            TermObjectController::class,
            3,
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE
        );
        $termObjectController->removeTermData(3);
    }
}
