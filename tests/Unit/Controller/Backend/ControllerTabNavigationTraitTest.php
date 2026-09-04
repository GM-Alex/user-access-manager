<?php
/**
 * ControllerTabNavigationTraitTest.php
 *
 * The ControllerTabNavigationTraitTest unit test class file.
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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UserAccessManager\Controller\Backend\ControllerTabNavigationTrait;

/**
 * Class ControllerTabNavigationTraitTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Backend
 * @coversDefaultClass \UserAccessManager\Controller\Backend\ControllerTabNavigationTrait
 */
class ControllerTabNavigationTraitTest extends TestCase
{
    /**
     * @return MockObject|ControllerTabNavigationTrait
     */
    private function getStub(): ControllerTabNavigationTrait|MockObject
    {
        $stub = $this->getMockForTrait(
            ControllerTabNavigationTrait::class,
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
        $controllerTabNavigationTrait = $this->getStub();

        $controllerTabNavigationTrait->expects($this->exactly(2))
            ->method('getRequestParameter')
            ->with('tab_group', 'groupOne')
            ->will($this->onConsecutiveCalls('groupTwo', 'groupOne'));

        self::assertEquals('groupTwo', $controllerTabNavigationTrait->getCurrentTabGroup());
        self::assertEquals('groupOne', $controllerTabNavigationTrait->getCurrentTabGroup());
    }

    /**
     * The group ends up in a css class and an element id, so anything which is not
     * a known group has to fall back to the default instead of being reflected.
     *
     * @group  unit
     * @covers ::getCurrentTabGroup()
     */
    public function testGetCurrentTabGroupFallsBackToTheDefaultForUnknownGroups()
    {
        $controllerTabNavigationTrait = $this->getStub();

        $controllerTabNavigationTrait->expects($this->once())
            ->method('getRequestParameter')
            ->with('tab_group', 'groupOne')
            ->will($this->returnValue('x&quot; onmouseover=alert(1) y=&quot;'));

        self::assertEquals('groupOne', $controllerTabNavigationTrait->getCurrentTabGroup());
    }

    /**
     * @group  unit
     * @covers ::getSections()
     */
    public function testGetSections()
    {
        $controllerTabNavigationTrait = $this->getStub();

        $controllerTabNavigationTrait->expects($this->exactly(3))
            ->method('getRequestParameter')
            ->with('tab_group', 'groupOne')
            ->will($this->onConsecutiveCalls(
                'unknownGroup',
                'groupOne',
                'groupTwo'
            ));

        self::assertEquals(
            ['groupOneSectionOne', 'groupOneSectionTwo', 'groupOneSectionThree'],
            $controllerTabNavigationTrait->getSections()
        );
        self::assertEquals(
            ['groupOneSectionOne', 'groupOneSectionTwo', 'groupOneSectionThree'],
            $controllerTabNavigationTrait->getSections()
        );
        self::assertEquals(
            ['groupTwoSectionOne', 'groupTwoSectionTwo'],
            $controllerTabNavigationTrait->getSections()
        );
    }

    /**
     * @group  unit
     * @covers ::getCurrentTabGroupSection()
     */
    public function testGetCurrentTabGroupSection()
    {
        $controllerTabNavigationTrait = $this->getStub();

        $controllerTabNavigationTrait->expects($this->exactly(4))
            ->method('getRequestParameter')
            ->withConsecutive(
                ['tab_group', 'groupOne'],
                ['tab_group_section', 'groupOneSectionOne'],
                ['tab_group', 'groupOne'],
                ['tab_group_section', 'groupTwoSectionOne']
            )
            ->will($this->onConsecutiveCalls(
                'groupOne',
                'groupOneSectionTwo',
                'groupTwo',
                'groupTwoSectionTwo'
            ));

        self::assertEquals(
            'groupOneSectionTwo',
            $controllerTabNavigationTrait->getCurrentTabGroupSection()
        );

        self::assertEquals(
            'groupTwoSectionTwo',
            $controllerTabNavigationTrait->getCurrentTabGroupSection()
        );
    }

    /**
     * The section is reflected into an element id and a css class, so a section
     * which does not belong to the current group has to fall back to the default.
     *
     * @group  unit
     * @covers ::getCurrentTabGroupSection()
     */
    public function testGetCurrentTabGroupSectionFallsBackToTheDefaultForUnknownSections()
    {
        $controllerTabNavigationTrait = $this->getStub();

        $controllerTabNavigationTrait->expects($this->exactly(4))
            ->method('getRequestParameter')
            ->withConsecutive(
                ['tab_group', 'groupOne'],
                ['tab_group_section', 'groupOneSectionOne'],
                ['tab_group', 'groupOne'],
                ['tab_group_section', 'groupOneSectionOne']
            )
            ->will($this->onConsecutiveCalls(
                'groupOne',
                'x&quot; onmouseover=alert(1) y=&quot;',
                'groupOne',
                // A section of a different group must not be accepted either.
                'groupTwoSectionOne'
            ));

        self::assertEquals(
            'groupOneSectionOne',
            $controllerTabNavigationTrait->getCurrentTabGroupSection()
        );

        self::assertEquals(
            'groupOneSectionOne',
            $controllerTabNavigationTrait->getCurrentTabGroupSection()
        );
    }

    /**
     * @group  unit
     * @covers ::getTabGroupLink()
     */
    public function testGetTabGroupLink()
    {
        $controllerTabNavigationTrait = $this->getStub();

        $controllerTabNavigationTrait->expects($this->exactly(2))
            ->method('getRequestUrl')
            ->will($this->returnValue('url/?page=page'));

        $_SERVER['REQUEST_URI'] = 'url/?page=page';

        self::assertEquals(
            'url/?page=page&tab_group=key',
            $controllerTabNavigationTrait->getTabGroupLink('key')
        );

        $_SERVER['REQUEST_URI'] = 'url/?page=page&tab_group=c';

        self::assertEquals(
            'url/?page=page&tab_group=key',
            $controllerTabNavigationTrait->getTabGroupLink('key')
        );
    }

    /**
     * @group  unit
     * @covers ::getTabGroupSectionLink()
     */
    public function testGetTabGroupSectionLink()
    {
        $controllerTabNavigationTrait = $this->getStub();

        $controllerTabNavigationTrait->expects($this->exactly(2))
            ->method('getRequestUrl')
            ->will($this->returnValue('url/?page=page'));

        $_SERVER['REQUEST_URI'] = 'url/?page=page';

        self::assertEquals(
            'url/?page=page&tab_group=group&tab_group_section=section',
            $controllerTabNavigationTrait->getTabGroupSectionLink('group', 'section')
        );

        $_SERVER['REQUEST_URI'] = 'url/?page=page&tab_group=c&tab_group_section=someSection';

        self::assertEquals(
            'url/?page=page&tab_group=group&tab_group_section=section',
            $controllerTabNavigationTrait->getTabGroupSectionLink('group', 'section')
        );
    }
}
