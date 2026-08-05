<?php
/**
 * ObjectInformationFactoryTest.php
 *
 * The ObjectInformationFactoryTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Controller\Backend\ObjectMembership;

use PHPUnit\Framework\TestCase;
use UserAccessManager\Controller\Backend\ObjectMembership\ObjectInformation;
use UserAccessManager\Controller\Backend\ObjectMembership\ObjectInformationFactory;

/**
 * Class ObjectInformationFactoryTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Backend
 * @coversDefaultClass \UserAccessManager\Controller\Backend\ObjectMembership\ObjectInformationFactory
 */
class ObjectInformationFactoryTest extends TestCase
{
    /**
     * @group unit
     * @return ObjectInformationFactory
     */
    public function testCanCreateInstance(): ObjectInformationFactory
    {
        $objectInformationFactory = new ObjectInformationFactory();

        self::assertInstanceOf(ObjectInformationFactory::class, $objectInformationFactory);

        return $objectInformationFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createObjectInformation()
     * @param ObjectInformationFactory $objectInformationFactory
     */
    public function testCreateBackendController(ObjectInformationFactory $objectInformationFactory)
    {
        self::assertInstanceOf(
            ObjectInformation::class,
            $objectInformationFactory->createObjectInformation()
        );
    }
}
