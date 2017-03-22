<?php
/**
 * ObjectCommandTest.php
 *
 * The ObjectCommandTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Command;

/**
 * Class ObjectCommandTest
 *
 * @package UserAccessManager\Command
 */
class ObjectCommandTest extends \UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\Command\ObjectCommand::__construct()
     */
    public function testCanCreateInstance()
    {
        $oObjectCommand = new ObjectCommand(
            $this->getWordpressCli(),
            $this->getAccessHandler(),
            $this->getUserGroupFactory()
        );

        self::assertInstanceOf('\UserAccessManager\Command\ObjectCommand', $oObjectCommand);
    }
}
