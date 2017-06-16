<?php
/**
 * LabelTraitTest.php
 *
 * The LabelTraitTest unit test class file.
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

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class LabelTraitTest
 *
 * @package UserAccessManager\Form
 */
class LabelTraitTest extends UserAccessManagerTestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LabelTrait
     */
    private function getStub()
    {
        return $this->getMockForTrait('\UserAccessManager\Form\LabelTrait');
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Form\LabelTrait::getLabel()
     */
    public function testGetGetLabel()
    {
        $labelTrait = $this->getStub();
        self::setValue($labelTrait, 'label', 'labelValue');
        self::assertEquals('labelValue', $labelTrait->getLabel());
    }
}
