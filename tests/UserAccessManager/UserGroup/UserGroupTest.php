<?php
/**
 * UserGroupTest.php
 *
 * The UserGroupTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\UserGroup;

/**
 * Class UserGroupTest
 *
 * @package UserAccessManager\UserGroup
 */
class UserGroupTest extends \UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\UserGroup::__construct()
     */
    public function testCanCreateInstance()
    {
        $oObjectHandler = $this->getObjectHandler();
        $oObjectHandler->expects($this->any())
            ->method('getAllObjectTypes')
            ->will($this->returnValue([]));


        $oUserGroup = new UserGroup(
            $this->getWrapper(),
            $this->getDatabase(),
            $this->getConfig(),
            $this->getCache(),
            $this->getUtil(),
            $oObjectHandler
        );

        self::assertInstanceOf('\UserAccessManager\UserGroup\UserGroup', $oUserGroup);
    }
}
