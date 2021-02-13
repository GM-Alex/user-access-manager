<?php
/**
 * FormHelperTest.php
 *
 * The FormHelperTest unit test class file.
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
use UserAccessManager\Config\ConfigParameter;
use UserAccessManager\Config\StringConfigParameter;
use UserAccessManager\Form\FormHelper;
use UserAccessManager\Form\Input;
use UserAccessManager\Form\MultipleFormElementValue;
use UserAccessManager\Form\Radio;
use UserAccessManager\Form\Select;
use UserAccessManager\Form\ValueSetFormElementValue;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class FormHelperTest
 *
 * @package UserAccessManager\Tests\Unit\Form
 * @coversDefaultClass \UserAccessManager\Form\FormHelper
 */
class FormHelperTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $formHelper = new FormHelper(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getFormFactory()
        );

        self::assertInstanceOf(FormHelper::class, $formHelper);
    }

    /**
     * @group   unit
     * @covers  ::getText()
     * @covers  ::getParameterText()
     * @covers  ::getObjectText()
     */
    public function testGetText()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(3))
            ->method('arrayFill')
            ->withConsecutive(
                [0, 1, 'category'],
                [0, 1, 'attachment'],
                [0, 2, 'post']
            )->will($this->returnCallback(function ($startIndex, $numberOfElements, $value) {
                return array_fill($startIndex, $numberOfElements, $value);
            }));

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(9))
            ->method('getPostTypes')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                ObjectHandler::ATTACHMENT_OBJECT_TYPE => $this->createTypeObject('attachment'),
                ObjectHandler::POST_OBJECT_TYPE => $this->createTypeObject('post'),
                ObjectHandler::PAGE_OBJECT_TYPE => $this->createTypeObject('page')
            ]));

        $wordpress->expects($this->exactly(9))
            ->method('getTaxonomies')
            ->with(['public' => true], 'objects')
            ->will($this->returnValue([
                'category' => $this->createTypeObject('category')
            ]));

        $formHelper = new FormHelper(
            $php,
            $wordpress,
            $this->getMainConfig(),
            $this->getFormFactory()
        );

        /**
         * @var MockObject|ConfigParameter $parameter
         */
        $parameter = $this->getMockForAbstractClass(
            ConfigParameter::class,
            [],
            '',
            false,
            true,
            true,
            ['getId']
        );

        $parameter->expects(self::any())
            ->method('getId')
            ->will($this->returnValue('test_id'));

        define('TXT_UAM_GROUP_KEY_SETTING', 'TEST');
        define('TXT_UAM_GROUP_KEY_SETTING_DESC', 'TEST_DESC');
        define('TXT_UAM_TEST_ID', 'TEST_ID');
        define('TXT_UAM_TEST_ID_DESC', 'TEST_ID_DESC');

        self::assertEquals('TEST', $formHelper->getText('group_key'));
        self::assertEquals('TEST_DESC', $formHelper->getText('group_key', true));

        self::assertEquals('TEST_ID', $formHelper->getParameterText($parameter, false, 'group_key'));
        self::assertEquals('TEST_ID_DESC', $formHelper->getParameterText($parameter, true, 'group_key'));

        self::assertEquals(
            'category settings|user-access-manager',
            $formHelper->getText('category')
        );
        self::assertEquals(
            'Set up the behaviour if the attachment is locked|user-access-manager',
            $formHelper->getText(ObjectHandler::ATTACHMENT_OBJECT_TYPE, true)
        );
        self::assertEquals(
            'TEST_ID',
            $formHelper->getParameterText($parameter, false, ObjectHandler::POST_OBJECT_TYPE)
        );
        self::assertEquals(
            'TEST_ID_DESC',
            $formHelper->getParameterText($parameter, true, ObjectHandler::POST_OBJECT_TYPE)
        );

        define('TXT_UAM_TEST', '%s %s');
        /**
         * @var MockObject|ConfigParameter $parameter
         */
        $parameter = $this->getMockForAbstractClass(
            ConfigParameter::class,
            [],
            '',
            false,
            true,
            true,
            ['getId']
        );

        $parameter->expects(self::any())
            ->method('getId')
            ->will($this->returnValue('test'));

        self::assertEquals(
            'post post',
            $formHelper->getParameterText($parameter, false, ObjectHandler::POST_OBJECT_TYPE)
        );
    }

    /**
     * @group  unit
     * @covers ::createMultipleFromElement()
     * @throws Exception
     */
    public function testCreateMultipleFromElement()
    {
        $multipleFormElementValue = $this->createMock(MultipleFormElementValue::class);
        $multipleFormElementValue->expects($this->once())
            ->method('setSubElement')
            ->with();

        $formFactory = $this->getFormFactory();

        $formFactory->expects($this->once())
            ->method('createInput')
            ->will($this->returnValue($this->createMock(Input::class)));

        $formFactory->expects($this->exactly(2))
            ->method('createMultipleFormElementValue')
            ->with('value', 'label')
            ->will($this->returnValue($multipleFormElementValue));

        /** @var ConfigParameter $parameter */
        $parameter = $this->createMock(StringConfigParameter::class);

        $formHelper = new FormHelper(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $formFactory
        );

        $formHelper->createMultipleFromElement('value', 'label');
        $formHelper->createMultipleFromElement('value', 'label', $parameter);
    }

    /**
     * @group  unit
     * @covers ::convertConfigParameter()
     * @covers ::convertSelectionParameter()
     * @throws Exception
     */
    public function testConvertConfigParameter()
    {
        $input = $this->createMock(Input::class);
        $formFactory = $this->getFormFactory();
        $formFactory->expects($this->exactly(2))
            ->method('createInput')
            ->with('stringId', 'stringValue', 'TXT_UAM_STRINGID', 'TXT_UAM_STRINGID_DESC')
            ->will($this->onConsecutiveCalls($input, $this->createMock(Input::class)));

        $multipleFormElementValueYes = $this->createMock(MultipleFormElementValue::class);
        $multipleFormElementValueNo = $this->createMock(MultipleFormElementValue::class);
        $radioSecondSelection = $this->createMock(MultipleFormElementValue::class);

        $formFactory->expects($this->exactly(4))
            ->method('createMultipleFormElementValue')
            ->withConsecutive(
                [true, TXT_UAM_YES],
                [false, TXT_UAM_NO],
                ['firstSelection', 'TXT_UAM_SELECTIONID_FIRSTSELECTION'],
                ['secondSelection', 'TXT_UAM_SELECTIONID_SECONDSELECTION']
            )
            ->will($this->onConsecutiveCalls(
                $multipleFormElementValueYes,
                $multipleFormElementValueNo,
                $this->createMock(MultipleFormElementValue::class),
                $radioSecondSelection
            ));

        $radio = $this->createMock(Radio::class);
        $formFactory->expects($this->exactly(2))
            ->method('createRadio')
            ->withConsecutive(
                [
                    'booleanId',
                    [$multipleFormElementValueYes, $multipleFormElementValueNo],
                    'booleanValue',
                    'TXT_UAM_BOOLEANID',
                    'TXT_UAM_BOOLEANID_DESC'
                ],
                [
                    'selectionId',
                    [$this->createMock(MultipleFormElementValue::class), $radioSecondSelection],
                    'selectionValue',
                    'TXT_UAM_SELECTIONID',
                    'TXT_UAM_SELECTIONID_DESC'
                ]
            )
            ->will($this->returnValue($radio));

        $firstValueSetFromElementValue = $this->createMock(ValueSetFormElementValue::class);
        $secondValueSetFromElementValue = $this->createMock(ValueSetFormElementValue::class);
        $select = $this->createMock(Select::class);
        $formFactory->expects($this->exactly(2))
            ->method('createValueSetFromElementValue')
            ->withConsecutive(
                ['firstSelection', 'TXT_UAM_SELECTIONID_FIRSTSELECTION'],
                ['secondSelection', 'TXT_UAM_SELECTIONID_SECONDSELECTION']
            )
            ->will($this->onConsecutiveCalls(
                $firstValueSetFromElementValue,
                $secondValueSetFromElementValue
            ));

        $formFactory->expects($this->once())
            ->method('createSelect')
            ->with(
                'selectionId',
                [$firstValueSetFromElementValue, $secondValueSetFromElementValue],
                'selectionValue',
                'TXT_UAM_SELECTIONID',
                'TXT_UAM_SELECTIONID_DESC'
            )
            ->will($this->returnValue($select));

        $formHelper = new FormHelper(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $formFactory
        );

        /**
         * @var MockObject|ConfigParameter $genericParameter
         */
        $genericParameter = $this->getMockForAbstractClass(
            ConfigParameter::class,
            [],
            '',
            false,
            true,
            true,
            ['getId']
        );

        self::assertNull($formHelper->convertConfigParameter($genericParameter));

        $stringParameter = $this->getConfigParameter('string');
        self::assertEquals($input, $formHelper->convertConfigParameter($stringParameter));

        $booleanParameter = $this->getConfigParameter('boolean');
        self::assertEquals($radio, $formHelper->convertConfigParameter($booleanParameter));

        $selectionParameter = $this->getConfigParameter('selection');
        $selectionParameter->expects($this->exactly(2))
            ->method('getSelections')
            ->will($this->returnValue(['firstSelection', 'secondSelection']));

        self::assertEquals($select, $formHelper->convertConfigParameter($selectionParameter));

        self::assertEquals(
            $radio,
            $formHelper->convertConfigParameter($selectionParameter, null, ['firstSelection' => $stringParameter])
        );
    }

    /**
     * @group  unit
     * @covers ::getSettingsForm()
     * @throws Exception
     */
    public function testGetSettingsFrom()
    {
        $five = $this->getConfigParameter('selection', 'Five');
        $five->expects($this->once())
            ->method('getSelections')
            ->will($this->returnValue(['a', 'b']));

        $config = $this->getMainConfig();
        $config->expects($this->once())
            ->method('getConfigParameters')
            ->will($this->returnValue(
                [
                    'one' => $this->getConfigParameter('string', 'One'),
                    'two' => $this->getConfigParameter('string', 'Two'),
                    'five' => $five,
                    'six' => $this->getConfigParameter('string', 'Six'),
                    'seven' => $this->getConfigParameter('string', 'Seven')
                ]
            ));

        $formInputOne = $this->createMock(Input::class);
        $formInputTwo = $this->createMock(Input::class);
        $formInputThree = $this->createMock(Input::class);
        $radioInput = $this->createMock(Radio::class);

        $form = $this->createMock('\UserAccessManager\Form\Form');
        $form->expects($this->exactly(4))
            ->method('addElement')
            ->withConsecutive(
                [$formInputOne],
                [$formInputTwo],
                [$formInputThree],
                [$radioInput]
            );

        $formFactory = $this->getFormFactory();
        $formFactory->expects($this->once())
            ->method('createFrom')
            ->will($this->returnValue($form));

        $formFactory->expects($this->exactly(3))
            ->method('createInput')
            ->withConsecutive(
                ['stringOneId', 'stringOneValue', 'TXT_UAM_STRINGONEID', 'TXT_UAM_STRINGONEID_DESC'],
                ['stringTwoId', 'stringTwoValue', 'TXT_UAM_STRINGTWOID', 'TXT_UAM_STRINGTWOID_DESC'],
                ['stringSevenId', 'stringSevenValue', 'TXT_UAM_STRINGSEVENID', 'TXT_UAM_STRINGSEVENID_DESC']
            )
            ->will($this->onConsecutiveCalls(
                $formInputOne,
                $formInputTwo,
                $formInputOne
            ));


        $multipleFormElementValueOne = $this->createMock(MultipleFormElementValue::class);
        $multipleFormElementValueTwo = $this->createMock(MultipleFormElementValue::class);

        $formFactory->expects($this->once())
            ->method('createRadio')
            ->withConsecutive(
                [
                    'selectionFiveId',
                    [$multipleFormElementValueOne, $multipleFormElementValueTwo],
                    'selectionFiveValue',
                    'TXT_UAM_SELECTIONFIVEID',
                    'TXT_UAM_SELECTIONFIVEID_DESC'
                ]
            )
            ->will($this->onConsecutiveCalls(
                $radioInput
            ));

        $formFactory->expects($this->exactly(2))
            ->method('createMultipleFormElementValue')
            ->withConsecutive(
                ['a', 'TXT_UAM_SELECTIONFIVEID_A'],
                ['b', 'TXT_UAM_SELECTIONFIVEID_B']
            )
            ->will($this->onConsecutiveCalls(
                $multipleFormElementValueOne,
                $multipleFormElementValueTwo
            ));

        $formHelper = new FormHelper(
            $this->getPhp(),
            $this->getWordpress(),
            $config,
            $formFactory
        );

        self::assertEquals(
            $form,
            $formHelper->getSettingsForm(
                [
                    'one',
                    'two',
                    'invalid',
                    $formInputThree,
                    'five' => [
                        'a' => 'invalid',
                        'b' => 'seven'
                    ]
                ]
            )
        );
    }

    /**
     * @group  unit
     * @covers ::getSettingsFormByConfig()
     * @throws Exception
     */
    public function testGetSettingsFormByConfig()
    {
        $config = $this->getConfig();
        $config->expects($this->once())
            ->method('getConfigParameters')
            ->will($this->returnValue(
                [
                    'one' => $this->getConfigParameter('string', 'One'),
                    'two' => $this->getConfigParameter('string', 'Two')
                ]
            ));

        $formInputOne = $this->createMock(Input::class);
        $formInputTwo = $this->createMock(Input::class);

        $form = $this->createMock('\UserAccessManager\Form\Form');
        $form->expects($this->exactly(2))
            ->method('addElement')
            ->withConsecutive(
                [$formInputOne],
                [$formInputTwo]
            );

        $formFactory = $this->getFormFactory();
        $formFactory->expects($this->once())
            ->method('createFrom')
            ->will($this->returnValue($form));

        $formFactory->expects($this->exactly(2))
            ->method('createInput')
            ->withConsecutive(
                ['stringOneId', 'stringOneValue', 'TXT_UAM_STRINGONEID', 'TXT_UAM_STRINGONEID_DESC'],
                ['stringTwoId', 'stringTwoValue', 'TXT_UAM_STRINGTWOID', 'TXT_UAM_STRINGTWOID_DESC']
            )
            ->will($this->onConsecutiveCalls(
                $formInputOne,
                $formInputTwo
            ));

        $formHelper = new FormHelper(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $formFactory
        );

        self::assertEquals($form, $formHelper->getSettingsFormByConfig($config));
    }
}
