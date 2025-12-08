<?php
/**
 * ValueTraitTest.php
 *
 * The ValueTraitTest unit test class file.
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
use UserAccessManager\Form\ValueTrait;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class ValueTraitTest
 *
 * @package UserAccessManager\Tests\Unit\Form
 * @coversDefaultClass \UserAccessManager\Form\ValueTrait
 */
class ValueTraitTest extends UserAccessManagerTestCase
{
    /**
     * @return MockObject|ValueTrait
     */
    private function getStub(): ValueTrait|MockObject
    {
        return $this->getMockForTrait(ValueTrait::class);
    }

    /**
     * @group  unit
     * @covers ::getValue()
     * @throws ReflectionException
     */
    public function testGetGetValue()
    {
        $valueTrait = $this->getStub();
        self::setValue($valueTrait, 'value', 'valueValue');
        self::assertEquals('valueValue', $valueTrait->getValue());
    }
}
