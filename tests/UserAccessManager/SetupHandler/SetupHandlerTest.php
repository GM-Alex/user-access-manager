<?php
/**
 * SetupHandlerTest.php
 *
 * The SetupHandlerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\SetupHandler;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Config\Config;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserAccessManager;
use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class SetupHandlerTest
 *
 * @package UserAccessManager\SetupHandler
 */
class SetupHandlerTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::__construct()
     */
    public function testCanCreateInstance()
    {
        $SetupHandler = new SetupHandler(
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        self::assertInstanceOf('\UserAccessManager\SetupHandler\SetupHandler', $SetupHandler);
    }

    /**
     * @param int $iNumberOfSites
     *
     * @return array
     */
    private function getSites($iNumberOfSites = 3)
    {
        $aSites = [];

        for ($iCount = 1; $iCount <= $iNumberOfSites; $iCount++) {
            /**
             * @var \stdClass $Site
             */
            $Site = $this->getMockBuilder('\WP_Site')->getMock();
            $Site->blog_id = $iCount;
            $aSites[] = $Site;
        }

        return $aSites;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::getBlogIds()
     */
    public function testGetBlogIds()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->once())
            ->method('getSites')
            ->will($this->returnValue($this->getSites()));

        $Database = $this->getDatabase();

        $Database->expects($this->once())
            ->method('getCurrentBlogId')
            ->will($this->returnValue(123));

        $SetupHandler = new SetupHandler(
            $Wordpress,
            $Database,
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        $aBlogIds = $SetupHandler->getBlogIds();
        self::assertEquals([123 => 123, 1 => 1, 2 => 2, 3 => 3], $aBlogIds);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::install()
     * @covers \UserAccessManager\SetupHandler\SetupHandler::runInstall()
     */
    public function testInstall()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->once())
            ->method('getSites')
            ->will($this->returnValue($this->getSites(1)));

        $Wordpress->expects(($this->exactly(3)))
            ->method('addOption')
            ->with('uam_db_version', UserAccessManager::DB_VERSION);

        $Wordpress->expects(($this->exactly(2)))
            ->method('switchToBlog')
            ->withConsecutive([1], [1]);

        $Database = $this->getDatabase();
        $Database->expects($this->exactly(3))
            ->method('getCharset')
            ->will($this->returnValue('CHARSET test'));

        $Database->expects($this->exactly(3))
            ->method('getUserGroupTable')
            ->will($this->returnValue('user_group_table'));

        $Database->expects($this->exactly(3))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('user_group_to_object_table'));

        $Database->expects($this->exactly(6))
            ->method('getVariable')
            ->will($this->onConsecutiveCalls(
                'invalid_table',
                'invalid_table',
                'user_group_table',
                'user_group_to_object_table',
                'invalid_table',
                'invalid_table'
            ));

        $Database->expects($this->exactly(2))
            ->method('getCurrentBlogId')
            ->will($this->returnValue(1));

        $ObjectHandler = $this->getObjectHandler();
        $FileHandler = $this->getFileHandler();

        $Database->expects($this->exactly(4))
            ->method('dbDelta')
            ->withConsecutive(
                [new MatchIgnoreWhitespace(
                    'CREATE TABLE user_group_table (
                        ID INT(11) NOT NULL AUTO_INCREMENT,
                        groupname TINYTEXT NOT NULL,
                        groupdesc TEXT NOT NULL,
                        read_access TINYTEXT NOT NULL,
                        write_access TINYTEXT NOT NULL,
                        ip_range MEDIUMTEXT NULL,
                        PRIMARY KEY (ID)
                    ) CHARSET test;'
                )],
                [new MatchIgnoreWhitespace(
                    'CREATE TABLE user_group_to_object_table (
                        object_id VARCHAR(64) NOT NULL,
                        general_object_type VARCHAR(64) NOT NULL,
                        object_type VARCHAR(64) NOT NULL,
                        group_id INT(11) NOT NULL,
                        PRIMARY KEY (object_id,object_type,group_id)
                    ) CHARSET test;'
                )],
                [new MatchIgnoreWhitespace(
                    'CREATE TABLE user_group_table (
                        ID INT(11) NOT NULL AUTO_INCREMENT,
                        groupname TINYTEXT NOT NULL,
                        groupdesc TEXT NOT NULL,
                        read_access TINYTEXT NOT NULL,
                        write_access TINYTEXT NOT NULL,
                        ip_range MEDIUMTEXT NULL,
                        PRIMARY KEY (ID)
                    ) CHARSET test;'
                )],
                [new MatchIgnoreWhitespace(
                    'CREATE TABLE user_group_to_object_table (
                        object_id VARCHAR(64) NOT NULL,
                        general_object_type VARCHAR(64) NOT NULL,
                        object_type VARCHAR(64) NOT NULL,
                        group_id INT(11) NOT NULL,
                        PRIMARY KEY (object_id,object_type,group_id)
                    ) CHARSET test;'
                )]
            );

        $SetupHandler = new SetupHandler(
            $Wordpress,
            $Database,
            $ObjectHandler,
            $FileHandler
        );

        $SetupHandler->install();
        $SetupHandler->install();
        $SetupHandler->install(true);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::isDatabaseUpdateNecessary()
     */
    public function testIsDatabaseUpdateNecessary()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->exactly(4))
            ->method('getSites')
            ->will($this->onConsecutiveCalls(
                $this->getSites(),
                $this->getSites(),
                $this->getSites(),
                []
            ));

        $Wordpress->expects($this->exactly(4))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, false, true, true));


        $Wordpress->expects($this->exactly(2))
            ->method('getOption')
            ->with('uam_db_version')
            ->will($this->onConsecutiveCalls('1000.0.0', '0.0'));

        $Database = $this->getDatabase();
        $Database->expects($this->exactly(3))
            ->method('getBlogPrefix')
            ->will($this->returnValue('prefix_'));

        $Database->expects($this->exactly(3))
            ->method('prepare')
            ->with('SELECT option_value FROM prefix_options WHERE option_name = \'%s\' LIMIT 1', 'uam_db_version')
            ->will($this->returnValue('preparedStatement'));

        $Database->expects($this->exactly(3))
            ->method('getVariable')
            ->with('preparedStatement')
            ->will($this->onConsecutiveCalls('1000.0.0', '0.0', '0.0'));

        $ObjectHandler = $this->getObjectHandler();
        $FileHandler = $this->getFileHandler();

        $SetupHandler = new SetupHandler(
            $Wordpress,
            $Database,
            $ObjectHandler,
            $FileHandler
        );

        self::assertFalse($SetupHandler->isDatabaseUpdateNecessary());
        self::assertTrue($SetupHandler->isDatabaseUpdateNecessary());
        self::assertTrue($SetupHandler->isDatabaseUpdateNecessary());
        self::assertTrue($SetupHandler->isDatabaseUpdateNecessary());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::update()
     */
    public function testUpdate()
    {
        $Wordpress = $this->getWordpress();
        $Wordpress->expects($this->exactly(3))
            ->method('getOption')
            ->withConsecutive(
                ['uam_db_version', false],
                ['uam_db_version', false],
                ['uam_version', '0']
            )
            ->will($this->onConsecutiveCalls('0', '0.0', '0.0'));

        $Wordpress->expects($this->once())
            ->method('deleteOption')
            ->with('allow_comments_locked');

        $Wordpress->expects($this->once())
            ->method('updateOption')
            ->with('uam_db_version', UserAccessManager::DB_VERSION);

        $Database = $this->getDatabase();
        $Database->expects($this->once())
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $Database->expects($this->once())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $Database->expects($this->once())
            ->method('getPrefix')
            ->will($this->returnValue('prefix_'));

        $Database->expects($this->once())
            ->method('getCharset')
            ->will($this->returnValue('CHARSET testCharset'));

        $Database->expects($this->once())
            ->method('getPostsTable')
            ->will($this->returnValue('postsTable'));

        $Database->expects($this->once())
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $Database->expects($this->exactly(2))
            ->method('getVariable')
            ->withConsecutive(
                ['SHOW TABLES LIKE \'userGroupTable\''],
                ['SHOW columns FROM userGroupTable LIKE \'ip_range\'']
            )
            ->will($this->onConsecutiveCalls(
                'userGroupTable',
                'not_ip_range'
            ));

        $DbObject = new \stdClass();
        $DbObject->groupId = 123;
        $DbObject->id = 321;

        $Database->expects($this->exactly(4))
            ->method('getResults')
            ->withConsecutive(
                [new MatchIgnoreWhitespace(
                    'SELECT post_id AS id, group_id AS groupId
                    FROM prefix_uam_accessgroup_to_post, postsTable WHERE post_id = ID
                    AND post_type = \'post\''
                )],
                [new MatchIgnoreWhitespace(
                    'SELECT category_id AS id, group_id AS groupId
                    FROM prefix_uam_accessgroup_to_category'
                )],
                [new MatchIgnoreWhitespace(
                    'SELECT user_id AS id, group_id AS groupId FROM prefix_uam_accessgroup_to_user'
                )],
                [new MatchIgnoreWhitespace(
                    'SELECT role_name AS id, group_id AS groupId FROM prefix_uam_accessgroup_to_role'
                )]
            )
            ->will($this->onConsecutiveCalls([$DbObject], [], [], []));

        $Database->expects($this->exactly(12))
            ->method('query')
            ->withConsecutive(
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE userGroupTable
                    ADD read_access TINYTEXT NOT NULL DEFAULT \'\', 
                    ADD write_access TINYTEXT NOT NULL DEFAULT \'\', 
                    ADD ip_range MEDIUMTEXT NULL DEFAULT \'\''
                )],
                [new MatchIgnoreWhitespace(
                    'UPDATE userGroupTable SET read_access = \'group\', write_access = \'group\''
                )],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE userGroupTable ADD ip_range MEDIUMTEXT NULL DEFAULT \'\''
                )],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE \'prefix_uam_accessgroup_to_object\'
                    CHANGE \'object_id\' \'object_id\' VARCHAR(64) CHARSET testCharset'
                )],
                [new MatchIgnoreWhitespace(
                    'DROP TABLE prefix_uam_accessgroup_to_post,
                    prefix_uam_accessgroup_to_user,
                    prefix_uam_accessgroup_to_category,
                    prefix_uam_accessgroup_to_role'
                )],
                [new MatchIgnoreWhitespace(
                    'ALTER TABLE `userGroupToObjectTable`
                    CHANGE `object_id` `object_id` VARCHAR(64) NOT NULL,
                    CHANGE `object_type` `object_type` VARCHAR(64) NOT NULL'
                )],
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
            ->will($this->onConsecutiveCalls(
                'userGroupTable',
                'ip_range'
            ));

        $Database->expects($this->once())
            ->method('update')
            ->with(
                'userGroupToObjectTable',
                ['object_type' => ObjectHandler::GENERAL_TERM_OBJECT_TYPE],
                ['object_type' => 'category']
            );

        $ObjectHandler = $this->getObjectHandler();

        $ObjectHandler->expects($this->once())
            ->method('getObjectTypes')
            ->will($this->returnValue(
                ['post', 'nothing', 'category', 'nothing', 'user', 'nothing', 'role', 'nothing']
            ));

        $ObjectHandler->expects($this->exactly(8))
            ->method('isPostType')
            ->withConsecutive(
                ['post'],
                ['nothing'],
                ['category'],
                ['nothing'],
                ['user'],
                ['nothing'],
                ['role'],
                ['nothing']
            )
            ->will($this->onConsecutiveCalls(true, false, false, false, false, false, false, false));

        $FileHandler = $this->getFileHandler();

        $SetupHandler = new SetupHandler(
            $Wordpress,
            $Database,
            $ObjectHandler,
            $FileHandler
        );

        self::assertFalse($SetupHandler->update());
        self::assertTrue($SetupHandler->update());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::uninstall()
     */
    public function testUninstall()
    {
        $Wordpress = $this->getWordpress();

        $Wordpress->expects($this->once())
            ->method('getSites')
            ->will($this->returnValue($this->getSites(1)));

        $Wordpress->expects(($this->exactly(3)))
            ->method('deleteOption')
            ->withConsecutive(
                [Config::ADMIN_OPTIONS_NAME],
                ['uam_version'],
                ['uam_db_version']
            );

        $Wordpress->expects(($this->once()))
            ->method('switchToBlog')
            ->withConsecutive([1]);

        $Database = $this->getDatabase();

        $Database->expects($this->once())
            ->method('getCurrentBlogId')
            ->will($this->returnValue(1));

        $Database->expects($this->once())
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $Database->expects($this->once())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $Database->expects($this->once())
            ->method('query')
            ->with(new MatchIgnoreWhitespace(
                'DROP TABLE userGroupTable, userGroupToObjectTable'
            ));

        $FileHandler = $this->getFileHandler();
        $FileHandler->expects($this->once())
            ->method('deleteFileProtection');

        $SetupHandler = new SetupHandler(
            $Wordpress,
            $Database,
            $this->getObjectHandler(),
            $FileHandler
        );

        $SetupHandler->uninstall();
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::deactivate()
     */
    public function testDeactivate()
    {
        $Wordpress = $this->getWordpress();
        $Database = $this->getDatabase();
        $ObjectHandler = $this->getObjectHandler();
        $FileHandler = $this->getFileHandler();

        $FileHandler->expects($this->exactly(2))
            ->method('deleteFileProtection')
            ->will($this->onConsecutiveCalls(false, true));

        $SetupHandler = new SetupHandler(
            $Wordpress,
            $Database,
            $ObjectHandler,
            $FileHandler
        );

        self::assertFalse($SetupHandler->deactivate());
        self::assertTrue($SetupHandler->deactivate());
    }
}
