<?php
/**
 * FormTest.php
 *
 * The FormTest unit test class file.
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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\FormElement;

/**
 * Class FormTest
 *
 * @package UserAccessManager\Tests\Unit\Form
 * @coversDefaultClass \UserAccessManager\Form\Form
 */
class FormTest extends TestCase
{
    /**
     * @group unit
     * @return Form
     */
    public function testCanCreateInstance(): Form
    {
        $form = new Form();

        self::assertInstanceOf(Form::class, $form);

        return $form;
    }

    /**
     * @group   unit
     * @covers  ::addElement()
     * @depends testCanCreateInstance
     * @param Form $form
     * @return Form
     */
    public function testAddElement(Form $form): Form
    {
        /**
         * @var MockObject|FormElement $firstFormElement
         */
        $firstFormElement = $this->createMock(FormElement::class);
        $firstFormElement->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('firstId'));

        /**
         * @var MockObject|FormElement $secondFormElement
         */
        $secondFormElement = $this->createMock(FormElement::class);
        $secondFormElement->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('secondId'));

        $form->addElement($firstFormElement);
        $form->addElement($secondFormElement);

        self::assertEquals(
            ['firstId' => $firstFormElement, 'secondId' => $secondFormElement],
            $form->getElements()
        );

        return $form;
    }

    /**
     * @group   unit
     * @covers  ::getElements()
     * @depends testAddElement
     * @param Form $form
     */
    public function testGetElements(Form $form)
    {
        $elements = $form->getElements();
        self::assertCount(2, $elements);
    }
}
