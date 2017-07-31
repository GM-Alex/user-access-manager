<?php

namespace UserAccessManager\Controller;

/**
 * Class AdminControllerTabNavigationTraitTest
 *
 * @package UserAccessManager\Controller
 * @coversDefaultClass \UserAccessManager\Controller\AdminControllerTabNavigationTrait
 */
class AdminControllerTabNavigationTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AdminControllerTabNavigationTrait
     */
    private function getStub()
    {
        $stub = $this->getMockForTrait(
            AdminControllerTabNavigationTrait::class,
            [],
            '',
            false,
            true,
            true,
            ['getRequestUrl', 'getRequestParameter', 'getTabGroups']
        );

        $stub->expects($this->any())
            ->method('getTabGroups')
            ->will($this->returnValue([
                'groupOne' => ['groupOneSectionOne', 'groupOneSectionTwo', 'groupOneSectionThree'],
                'groupTwo' => ['groupTwoSectionOne', 'groupTwoSectionTwo']
            ]));

        return $stub;
    }

    /**
     * @group  unit
     * @covers ::getCurrentTabGroup()
     */
    public function testGetCurrentTabGroup()
    {
        $adminControllerTabNavigationTrait = $this->getStub();

        $adminControllerTabNavigationTrait->expects($this->once())
            ->method('getRequestParameter')
            ->with('tab_group', 'groupOne')
            ->will($this->returnValue('requestParameter'));

        self::assertEquals(
            'requestParameter',
            $adminControllerTabNavigationTrait->getCurrentTabGroup()
        );
    }

    /**
     * @group  unit
     * @covers ::getSections()
     */
    public function testGetSections()
    {
        $adminControllerTabNavigationTrait = $this->getStub();

        $adminControllerTabNavigationTrait->expects($this->exactly(3))
            ->method('getRequestParameter')
            ->with('tab_group', 'groupOne')
            ->will($this->onConsecutiveCalls(
                'requestParameter',
                'groupOne',
                'groupTwo'
            ));

        self::assertEquals([], $adminControllerTabNavigationTrait->getSections());
        self::assertEquals(
            ['groupOneSectionOne', 'groupOneSectionTwo', 'groupOneSectionThree'],
            $adminControllerTabNavigationTrait->getSections()
        );
        self::assertEquals(
            ['groupTwoSectionOne', 'groupTwoSectionTwo'],
            $adminControllerTabNavigationTrait->getSections()
        );
    }

    /**
     * @group  unit
     * @covers ::getCurrentTabGroupSection()
     */
    public function testGetCurrentTabGroupSection()
    {
        $adminControllerTabNavigationTrait = $this->getStub();

        $adminControllerTabNavigationTrait->expects($this->exactly(4))
            ->method('getRequestParameter')
            ->withConsecutive(
                ['tab_group', 'groupOne'],
                ['tab_group_section', 'groupOneSectionOne'],
                ['tab_group', 'groupOne'],
                ['tab_group_section', 'groupTwoSectionOne']
            )
            ->will($this->onConsecutiveCalls(
                'requestParameter',
                'sectionRequestParameterOne',
                'groupTwo',
                'sectionRequestParameterTwo'
            ));

        self::assertEquals(
            'sectionRequestParameterOne',
            $adminControllerTabNavigationTrait->getCurrentTabGroupSection()
        );

        self::assertEquals(
            'sectionRequestParameterTwo',
            $adminControllerTabNavigationTrait->getCurrentTabGroupSection()
        );
    }

    /**
     * @group  unit
     * @covers ::getTabGroupLink()
     */
    public function testGetTabGroupLink()
    {
        $adminControllerTabNavigationTrait = $this->getStub();

        $adminControllerTabNavigationTrait->expects($this->exactly(2))
            ->method('getRequestUrl')
            ->will($this->returnValue('url/?page=page'));

        $_SERVER['REQUEST_URI'] = 'url/?page=page';

        self::assertEquals(
            'url/?page=page&tab_group=key',
            $adminControllerTabNavigationTrait->getTabGroupLink('key')
        );

        $_SERVER['REQUEST_URI'] = 'url/?page=page&tab_group=c';

        self::assertEquals(
            'url/?page=page&tab_group=key',
            $adminControllerTabNavigationTrait->getTabGroupLink('key')
        );
    }

    /**
     * @group  unit
     * @covers ::getTabGroupSectionLink()
     */
    public function testGetTabGroupSectionLink()
    {
        $adminControllerTabNavigationTrait = $this->getStub();

        $adminControllerTabNavigationTrait->expects($this->exactly(2))
            ->method('getRequestUrl')
            ->will($this->returnValue('url/?page=page'));

        $_SERVER['REQUEST_URI'] = 'url/?page=page';

        self::assertEquals(
            'url/?page=page&tab_group=group&tab_group_section=section',
            $adminControllerTabNavigationTrait->getTabGroupSectionLink('group', 'section')
        );

        $_SERVER['REQUEST_URI'] = 'url/?page=page&tab_group=c&tab_group_section=someSection';

        self::assertEquals(
            'url/?page=page&tab_group=group&tab_group_section=section',
            $adminControllerTabNavigationTrait->getTabGroupSectionLink('group', 'section')
        );
    }
}
