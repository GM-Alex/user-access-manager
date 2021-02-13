<?php
/**
 * MultipleFormElementValueTest.php
 *
 * The MultipleFormElementValueTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Form;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\Input;
use UserAccessManager\Form\MultipleFormElement;
use UserAccessManager\Form\MultipleFormElementValue;

/**
 * Class MultipleFormElementValueTest
 *
 * @package UserAccessManager\Tests\Unit\Form
 * @coversDefaultClass \UserAccessManager\Form\MultipleFormElementValue
 */
class MultipleFormElementValueTest extends TestCase
{
    /**
     * @group unit
     * @return MultipleFormElementValue
     */
    public function testCanCreateInstance(): MultipleFormElementValue
    {
        $multipleFormElementValue = new MultipleFormElementValue('value', 'label');
        self::assertInstanceOf(MultipleFormElementValue::class, $multipleFormElementValue);

        return $multipleFormElementValue;
    }

    /**
     * @group             unit
     * @covers            ::setSubElement()
     * @depends           testCanCreateInstance
     * @param MultipleFormElementValue $multipleFormElementValue
     * @throws Exception
     */
    public function testSetSubElementException(MultipleFormElementValue $multipleFormElementValue)
    {
        $this->expectException(Exception::class);
        /**
         * @var MockObject|MultipleFormElement $subElement
         */
        $subElement = $this->createMock(MultipleFormElement::class);
        $multipleFormElementValue->setSubElement($subElement);
    }

    /**
     * @group   unit
     * @covers  ::setSubElement()
     * @depends testCanCreateInstance
     * @param MultipleFormElementValue $multipleFormElementValue
     * @return MultipleFormElementValue
     * @throws Exception
     */
    public function testSetSubElement(MultipleFormElementValue $multipleFormElementValue): MultipleFormElementValue
    {
        /**
         * @var MockObject|Input $subElement
         */
        $subElement = $this->createMock(Input::class);
        $multipleFormElementValue->setSubElement($subElement);

        self::assertEquals($subElement, $multipleFormElementValue->getSubElement());

        return $multipleFormElementValue;
    }

    /**
     * @group   unit
     * @covers  ::getSubElement()
     * @depends testSetSubElement
     * @param MultipleFormElementValue $multipleFormElementValue
     */
    public function testGetSubElement(MultipleFormElementValue $multipleFormElementValue)
    {
        /**
         * @var MockObject|Input $subElement
         */
        $subElement = $this->createMock(Input::class);
        self::assertEquals($subElement, $multipleFormElementValue->getSubElement());
    }
}
