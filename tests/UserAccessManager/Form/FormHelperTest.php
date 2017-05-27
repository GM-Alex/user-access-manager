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
namespace UserAccessManager\Form;

use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class FormHelperTest
 *
 * @package UserAccessManager\Form
 */
class FormHelperTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Form\FormHelper::__construct()
     */
    public function testCanCreateInstance()
    {
        $formHelper = new FormHelper(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getFormFactory()
        );

        self::assertInstanceOf('\UserAccessManager\Form\FormHelper', $formHelper);
    }

    /**
     * @param string $name
     *
     * @return \stdClass
     */
    private function createTypeObject($name)
    {
        $type = new \stdClass();
        $type->labels = new \stdClass();
        $type->labels->name = $name;

        return $type;
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\Form\FormHelper::getText()
     * @covers  \UserAccessManager\Form\FormHelper::getParameterText()
     * @covers  \UserAccessManager\Form\FormHelper::getObjectText()
     */
    public function testGetText()
    {
        $php = $this->getPhp();
        $php->expects($this->exactly(5))
            ->method('arrayFill')
            ->withConsecutive(
                [0, 1, 'category'],
                [0, 1, 'attachment'],
                [0, 0, 'post'],
                [0, 0, 'post'],
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
            $this->getConfig(),
            $this->getFormFactory()
        );

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\ConfigParameter $parameter
         */
        $parameter = $this->getMockForAbstractClass(
            '\UserAccessManager\Config\ConfigParameter',
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
         * @var \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\ConfigParameter $parameter
         */
        $parameter = $this->getMockForAbstractClass(
            '\UserAccessManager\Config\ConfigParameter',
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
     * Returns a config parameter mock.
     *
     * @param string $type
     * @param string $postFix
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\ConfigParameter
     */
    private function getConfigParameter($type, $postFix = '')
    {
        $type = strtolower($type);
        $className = ucfirst($type).'ConfigParameter';

        $parameter = $this->createMock("\UserAccessManager\Config\\{$className}");
        $parameter->expects($this->any())
            ->method('getId')
            ->will($this->returnValue("{$type}{$postFix}Id"));
        $parameter->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue("{$type}{$postFix}Value"));

        return $parameter;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Form\FormHelper::convertConfigParameter()
     */
    public function testConvertConfigParameter()
    {
        $formFactory = $this->getFormFactory();
        $formFactory->expects($this->once())
            ->method('createInput')
            ->with('stringId', 'stringValue', 'TXT_UAM_STRINGID', 'TXT_UAM_STRINGID_DESC')
            ->will($this->returnValue('input'));

        $formFactory->expects($this->exactly(2))
            ->method('createMultipleFormElementValue')
            ->withConsecutive(
                [true, TXT_UAM_YES],
                [false, TXT_UAM_NO]
            )
            ->will($this->onConsecutiveCalls(
                'multipleFormElementValueYes',
                'multipleFormElementValueNo'
            ));

        $formFactory->expects($this->once())
            ->method('createRadio')
            ->with(
                'booleanId',
                ['multipleFormElementValueYes', 'multipleFormElementValueNo'],
                'booleanValue',
                'TXT_UAM_BOOLEANID',
                'TXT_UAM_BOOLEANID_DESC'
            )
            ->will($this->returnValue('radio'));

        $formFactory->expects($this->exactly(2))
            ->method('createValueSetFromElementValue')
            ->withConsecutive(
                ['firstSelection', 'TXT_UAM_SELECTIONID_FIRSTSELECTION'],
                ['secondSelection', 'TXT_UAM_SELECTIONID_SECONDSELECTION']
            )
            ->will($this->onConsecutiveCalls(
                'firstValueSetFromElementValue',
                'secondValueSetFromElementValue'
            ));

        $formFactory->expects($this->once())
            ->method('createSelect')
            ->with(
                'selectionId',
                ['firstValueSetFromElementValue', 'secondValueSetFromElementValue'],
                'selectionValue',
                'TXT_UAM_SELECTIONID',
                'TXT_UAM_SELECTIONID_DESC'
            )
            ->will($this->returnValue('select'));

        $formHelper = new FormHelper(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $formFactory
        );

        /**
         * @var \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\ConfigParameter $genericParameter
         */
        $genericParameter = $this->getMockForAbstractClass(
            '\UserAccessManager\Config\ConfigParameter',
            [],
            '',
            false,
            true,
            true,
            ['getId']
        );

        self::assertNull($formHelper->convertConfigParameter($genericParameter));

        $stringParameter = $this->getConfigParameter('string');
        self::assertEquals('input', $formHelper->convertConfigParameter($stringParameter));

        $booleanParameter = $this->getConfigParameter('boolean');
        self::assertEquals('radio', $formHelper->convertConfigParameter($booleanParameter));

        $selectionParameter = $this->getConfigParameter('selection');
        $selectionParameter->expects($this->once())
            ->method('getSelections')
            ->will($this->returnValue(['firstSelection', 'secondSelection']));

        self::assertEquals('select', $formHelper->convertConfigParameter($selectionParameter));
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Form\FormHelper::getSettingsFrom()
     */
    public function testGetSettingsFrom()
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

        $formInputOne = $this->createMock('\UserAccessManager\Form\Input');
        $formInputTwo = $this->createMock('\UserAccessManager\Form\Input');
        $formInputThree = $this->createMock('\UserAccessManager\Form\Input');

        $form = $this->createMock('\UserAccessManager\Form\Form');
        $form->expects($this->exactly(3))
            ->method('addElement')
            ->withConsecutive(
                [$formInputOne],
                [$formInputTwo],
                [$formInputThree]
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
            $config,
            $formFactory
        );

        self::assertEquals($form, $formHelper->getSettingsFrom(['one', 'two', 'invalid', $formInputThree]));
    }
}
