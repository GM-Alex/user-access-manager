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
 * Class FormFactoryTest
 *
 * @package UserAccessManager\Form
 */
class FormFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group  unit
     *
     * @return FormFactory
     */
    public function testCanCreateInstance()
    {
        $formFactory = new FormFactory();

        self::assertInstanceOf(FormFactory::class, $formFactory);

        return $formFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Form\FormFactory::createFrom()
     *
     * @param FormFactory $formFactory
     */
    public function testCreateFrom(FormFactory $formFactory)
    {
        self::assertInstanceOf(Form::class, $formFactory->createFrom());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Form\FormFactory::createValueSetFromElementValue()
     *
     * @param FormFactory $formFactory
     */
    public function testCreateValueSetFromElementValue(FormFactory $formFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Form\ValueSetFormElementValue',
            $formFactory->createValueSetFromElementValue('value', 'label')
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Form\FormFactory::createMultipleFormElementValue()
     *
     * @param FormFactory $formFactory
     */
    public function testCreateMultipleFormElementValue(FormFactory $formFactory)
    {
        self::assertInstanceOf(
            '\UserAccessManager\Form\MultipleFormElementValue',
            $formFactory->createMultipleFormElementValue('value', 'label')
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Form\FormFactory::createInput()
     *
     * @param FormFactory $formFactory
     */
    public function testCreateInput(FormFactory $formFactory)
    {
        self::assertInstanceOf(Input::class, $formFactory->createInput('id'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Form\FormFactory::createTextarea()
     *
     * @param FormFactory $formFactory
     */
    public function testCreateTextarea(FormFactory $formFactory)
    {
        self::assertInstanceOf(Textarea::class, $formFactory->createTextarea('id'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Form\FormFactory::createSelect()
     *
     * @param FormFactory $formFactory
     */
    public function testCreateSelect(FormFactory $formFactory)
    {
        self::assertInstanceOf(Select::class, $formFactory->createSelect('id', []));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\Form\FormFactory::createRadio()
     *
     * @param FormFactory $formFactory
     */
    public function testCreateRadio(FormFactory $formFactory)
    {
        self::assertInstanceOf(Radio::class, $formFactory->createRadio('id', []));
    }
}
