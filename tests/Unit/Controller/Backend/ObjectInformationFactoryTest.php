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
namespace UserAccessManager\Tests\Unit\Controller\Backend;

use UserAccessManager\Controller\Backend\ObjectInformation;
use UserAccessManager\Controller\Backend\ObjectInformationFactory;

/**
 * Class ObjectInformationFactoryTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Backend
 * @coversDefaultClass \UserAccessManager\Controller\Backend\ObjectInformationFactory
 */
class ObjectInformationFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group unit
     *
     * @return ObjectInformationFactory
     */
    public function testCanCreateInstance()
    {
        $objectInformationFactory = new ObjectInformationFactory();

        self::assertInstanceOf(ObjectInformationFactory::class, $objectInformationFactory);

        return $objectInformationFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createObjectInformation()
     *
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
