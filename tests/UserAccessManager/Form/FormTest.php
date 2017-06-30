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
namespace UserAccessManager\Form;

/**
 * Class FormTest
 *
 * @package UserAccessManager\Form
 */
class FormTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group unit
     *
     * @return Form
     */
    public function testCanCreateInstance()
    {
        $form = new Form();

        self::assertInstanceOf(Form::class, $form);

        return $form;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Form\Form::addElement()
     * @depends testCanCreateInstance
     *
     * @param Form $form
     *
     * @return Form
     */
    public function testAddElement(Form $form)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Form\FormElement $firstFormElement
         */
        $firstFormElement = $this->createMock('\UserAccessManager\Form\FormElement');
        $firstFormElement->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('firstId'));

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Form\FormElement $secondFormElement
         */
        $secondFormElement = $this->createMock('\UserAccessManager\Form\FormElement');
        $secondFormElement->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('secondId'));

        $form->addElement($firstFormElement);
        $form->addElement($secondFormElement);

        self::assertAttributeEquals(
            ['firstId' => $firstFormElement, 'secondId' => $secondFormElement],
            'elements',
            $form
        );

        return $form;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Form\Form::getElements()
     * @depends testCanCreateInstance
     *
     * @param Form $form
     */
    public function testGetElements(Form $form)
    {
        $elements = $form->getElements();
        self::assertEquals(2, count($elements));
    }
}
