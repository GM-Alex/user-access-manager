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

use Exception;
use PHPUnit\Framework\TestCase;
use UserAccessManager\Form\Form;
use UserAccessManager\Form\FormFactory;
use UserAccessManager\Form\Input;
use UserAccessManager\Form\MultipleFormElementValue;
use UserAccessManager\Form\Radio;
use UserAccessManager\Form\Select;
use UserAccessManager\Form\Textarea;
use UserAccessManager\Form\ValueSetFormElementValue;

/**
 * Class FormFactoryTest
 *
 * @package UserAccessManager\Tests\Unit\Form
 * @coversDefaultClass \UserAccessManager\Form\FormFactory
 */
class FormFactoryTest extends TestCase
{
    /**
     * @group  unit
     * @return FormFactory
     */
    public function testCanCreateInstance(): FormFactory
    {
        $formFactory = new FormFactory();

        self::assertInstanceOf(FormFactory::class, $formFactory);

        return $formFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createFrom()
     * @param FormFactory $formFactory
     */
    public function testCreateFrom(FormFactory $formFactory)
    {
        self::assertInstanceOf(Form::class, $formFactory->createFrom());
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createValueSetFromElementValue()
     * @param FormFactory $formFactory
     */
    public function testCreateValueSetFromElementValue(FormFactory $formFactory)
    {
        self::assertInstanceOf(
            ValueSetFormElementValue::class,
            $formFactory->createValueSetFromElementValue('value', 'label')
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createMultipleFormElementValue()
     * @param FormFactory $formFactory
     */
    public function testCreateMultipleFormElementValue(FormFactory $formFactory)
    {
        self::assertInstanceOf(
            MultipleFormElementValue::class,
            $formFactory->createMultipleFormElementValue('value', 'label')
        );
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createInput()
     * @param FormFactory $formFactory
     */
    public function testCreateInput(FormFactory $formFactory)
    {
        self::assertInstanceOf(Input::class, $formFactory->createInput('id'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createTextarea()
     * @param FormFactory $formFactory
     */
    public function testCreateTextarea(FormFactory $formFactory)
    {
        self::assertInstanceOf(Textarea::class, $formFactory->createTextarea('id'));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createSelect()
     * @param FormFactory $formFactory
     */
    public function testCreateSelect(FormFactory $formFactory)
    {
        self::assertInstanceOf(Select::class, $formFactory->createSelect('id', []));
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createRadio()
     * @param FormFactory $formFactory
     * @throws Exception
     */
    public function testCreateRadio(FormFactory $formFactory)
    {
        self::assertInstanceOf(Radio::class, $formFactory->createRadio('id', []));
    }
}
