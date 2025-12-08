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

namespace UserAccessManager\Tests\Unit\Form;

use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use UserAccessManager\Form\LabelTrait;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class LabelTraitTest
 *
 * @package UserAccessManager\Tests\Unit\Form
 * @coversDefaultClass \UserAccessManager\Form\LabelTrait
 */
class LabelTraitTest extends UserAccessManagerTestCase
{
    /**
     * @return MockObject|LabelTrait
     */
    private function getStub(): MockObject|LabelTrait
    {
        return $this->getMockForTrait(LabelTrait::class);
    }

    /**
     * @group  unit
     * @covers ::getLabel()
     * @throws ReflectionException
     */
    public function testGetGetLabel()
    {
        $labelTrait = $this->getStub();
        self::setValue($labelTrait, 'label', 'labelValue');
        self::assertEquals('labelValue', $labelTrait->getLabel());
    }
}
