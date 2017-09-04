<?php
/**
 * Update4Test.php
 *
 * The Update4Test unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Setup\Update;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Setup\Update\Update4;
use UserAccessManager\Tests\UserAccessManagerTestCase;

/**
 * Class Update4Test
 *
 * @package UserAccessManager\Tests\Setup\Update
 * @coversDefaultClass \UserAccessManager\Setup\Update\Update4
 */
class Update4Test extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $update = new Update4(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(Update4::class, $update);
    }

    /**
     * @group  unit
     * @covers ::getVersion()
     */
    public function testGetVersion()
    {
        $update = new Update4(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertEquals('1.4', $update->getVersion());
    }

    /**
     * @group  unit
     * @covers ::update()
     */
    public function testUpdate()
    {
        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->once())
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $database->expects($this->exactly(6))
            ->method('query')
            ->withConsecutive(
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE userGroupToObjectTable
                    ADD general_object_type VARCHAR(64) NOT NULL AFTER object_id'
                )],
                [new MatchIgnoreWhitespace(
                    'UPDATE userGroupToObjectTable
                    SET general_object_type = \'_post_\'
                    WHERE object_type IN (\'post\', \'page\', \'attachment\')'
                )],
                [new MatchIgnoreWhitespace(
                    'UPDATE userGroupToObjectTable
                    SET general_object_type = \'_role_\'
                    WHERE object_type = \'role\''
                )],
                [new MatchIgnoreWhitespace(
                    'UPDATE userGroupToObjectTable
                    SET general_object_type = \'_user_\'
                    WHERE object_type = \'user\''
                )],
                [new MatchIgnoreWhitespace(
                    'UPDATE userGroupToObjectTable
                    SET general_object_type = \'_term_\'
                    WHERE object_type = \'term\''
                )],
                [new MatchIgnoreWhitespace(
                    'UPDATE userGroupToObjectTable AS gto
                    LEFT JOIN termTaxonomyTable AS tt 
                      ON gto.object_id = tt.term_id
                    SET gto.object_type = tt.taxonomy
                    WHERE gto.general_object_type = \'_term_\''
                )]
            )
            ->will($this->returnValue(true));

        $update = new Update4($database, $this->getObjectHandler());
        self::assertTrue($update->update());
    }
}
