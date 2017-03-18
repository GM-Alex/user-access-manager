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
use UserAccessManager\UserAccessManager;

/**
 * Class SetupHandlerTest
 *
 * @package UserAccessManager\SetupHandler
 */
class SetupHandlerTest extends \UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::__construct()
     */
    public function testCanCreateInstance()
    {
        $oSetupHandler = new SetupHandler(
            $this->getWrapper(),
            $this->getDatabase(),
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        self::assertInstanceOf('\UserAccessManager\SetupHandler\SetupHandler', $oSetupHandler);
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
             * @var \stdClass $oSite
             */
            $oSite = $this->getMockBuilder('\WP_Site')->getMock();
            $oSite->blog_id = $iCount;
            $aSites[] = $oSite;
        }

        return $aSites;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::getBlogIds()
     */
    public function testGetBlogIds()
    {
        $oWrapper = $this->getWrapper();

        $oWrapper->expects($this->exactly(1))
            ->method('getSites')
            ->will($this->returnValue($this->getSites()));

        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->exactly(1))
            ->method('getCurrentBlogId')
            ->will($this->returnValue(123));

        $oSetupHandler = new SetupHandler(
            $oWrapper,
            $oDatabase,
            $this->getObjectHandler(),
            $this->getFileHandler()
        );

        $aBlogIds = $oSetupHandler->getBlogIds();
        self::assertEquals([123 => 123, 1 => 1, 2 => 2, 3 => 3], $aBlogIds);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::install()
     * @covers \UserAccessManager\SetupHandler\SetupHandler::_install()
     */
    public function testInstall()
    {
        $oWrapper = $this->getWrapper();

        $oWrapper->expects($this->exactly(1))
            ->method('getSites')
            ->will($this->returnValue($this->getSites(1)));

        $oWrapper->expects(($this->exactly(3)))
            ->method('addOption')
            ->with('uam_db_version', UserAccessManager::DB_VERSION);

        $oWrapper->expects(($this->exactly(2)))
            ->method('switchToBlog')
            ->withConsecutive([1], [1]);

        $oDatabase = $this->getDatabase();
        $oDatabase->expects($this->exactly(3))
            ->method('getCharset')
            ->will($this->returnValue('CHARSET test'));

        $oDatabase->expects($this->exactly(3))
            ->method('getUserGroupTable')
            ->will($this->returnValue('user_group_table'));

        $oDatabase->expects($this->exactly(3))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('user_group_to_object_table'));

        $oDatabase->expects($this->exactly(6))
            ->method('getVariable')
            ->will($this->onConsecutiveCalls(
                'invalid_table',
                'invalid_table',
                'user_group_table',
                'user_group_to_object_table',
                'invalid_table',
                'invalid_table'
            ));

        $oDatabase->expects($this->exactly(2))
            ->method('getCurrentBlogId')
            ->will($this->returnValue(1));

        $oObjectHandler = $this->getObjectHandler();
        $oFileHandler = $this->getFileHandler();

        $oDatabase->expects($this->exactly(4))
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

        $oSetupHandler = new SetupHandler($oWrapper, $oDatabase, $oObjectHandler, $oFileHandler);
        $oSetupHandler->install();
        $oSetupHandler->install();
        $oSetupHandler->install(true);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::isDatabaseUpdateNecessary()
     */
    public function testIsDatabaseUpdateNecessary()
    {
        $oWrapper = $this->getWrapper();

        $oWrapper->expects($this->exactly(3))
            ->method('getSites')
            ->will($this->returnValue($this->getSites()));

        $oWrapper->expects($this->exactly(3))
            ->method('isSuperAdmin')
            ->will($this->onConsecutiveCalls(false, false, true));


        $oWrapper->expects($this->exactly(2))
            ->method('getOption')
            ->with('uam_db_version')
            ->will($this->onConsecutiveCalls('1000.0.0', '0.0'));

        $oDatabase = $this->getDatabase();
        $oDatabase->expects($this->exactly(2))
            ->method('getBlogPrefix')
            ->will($this->returnValue('prefix_'));

        $oDatabase->expects($this->exactly(2))
            ->method('prepare')
            ->with('SELECT option_value FROM prefix_options WHERE option_name = \'%s\' LIMIT 1', 'uam_db_version')
            ->will($this->returnValue('preparedStatement'));

        $oDatabase->expects($this->exactly(2))
            ->method('getVariable')
            ->with('preparedStatement')
            ->will($this->onConsecutiveCalls('1000.0.0', '0.0'));

        $oObjectHandler = $this->getObjectHandler();
        $oFileHandler = $this->getFileHandler();

        $oSetupHandler = new SetupHandler($oWrapper, $oDatabase, $oObjectHandler, $oFileHandler);
        self::assertFalse($oSetupHandler->isDatabaseUpdateNecessary());
        self::assertTrue($oSetupHandler->isDatabaseUpdateNecessary());
        self::assertTrue($oSetupHandler->isDatabaseUpdateNecessary());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::update()
     */
    public function testUpdate()
    {
        $oWrapper = $this->getWrapper();
        $oWrapper->expects($this->exactly(3))
            ->method('getOption')
            ->withConsecutive(
                ['uam_db_version', false],
                ['uam_db_version', false],
                ['uam_version', false]
            )
            ->will($this->onConsecutiveCalls(null, '0.0', '0.0'));

        $oWrapper->expects($this->exactly(1))
            ->method('deleteOption')
            ->with('allow_comments_locked');

        $oWrapper->expects($this->exactly(1))
            ->method('updateOption')
            ->with('uam_db_version', UserAccessManager::DB_VERSION);

        $oDatabase = $this->getDatabase();
        $oDatabase->expects($this->exactly(1))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $oDatabase->expects($this->exactly(1))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $oDatabase->expects($this->exactly(1))
            ->method('getPrefix')
            ->will($this->returnValue('prefix_'));

        $oDatabase->expects($this->exactly(1))
            ->method('getCharset')
            ->will($this->returnValue('CHARSET testCharset'));

        $oDatabase->expects($this->exactly(1))
            ->method('getPostsTable')
            ->will($this->returnValue('postsTable'));

        $oDatabase->expects($this->exactly(1))
            ->method('getTermTaxonomyTable')
            ->will($this->returnValue('termTaxonomyTable'));

        $oDatabase->expects($this->exactly(2))
            ->method('getVariable')
            ->withConsecutive(
                ['SHOW TABLES LIKE \'userGroupTable\''],
                ['SHOW columns FROM userGroupTable LIKE \'ip_range\'']
            )
            ->will($this->onConsecutiveCalls(
                'userGroupTable',
                'not_ip_range'
            ));

        $oDbObject = new \stdClass();
        $oDbObject->groupId = 123;
        $oDbObject->id = 321;

        $oDatabase->expects($this->exactly(4))
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
            ->will($this->onConsecutiveCalls(
                [$oDbObject], [], [], []
            ));

        $oDatabase->expects($this->exactly(12))
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
                    'ALTER TABLE \'prefix_uam_accessgroup_to_object\' CHANGE \'object_id\' \'object_id\' VARCHAR(64) CHARSET testCharset'
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

        $oObjectHandler = $this->getObjectHandler();

        $oObjectHandler->expects($this->exactly(1))
            ->method('getObjectTypes')
            ->will($this->returnValue(['post', 'category', 'user', 'role', 'nothing']));

        $oObjectHandler->expects($this->exactly(5))
            ->method('isPostType')
            ->withConsecutive(['post'], ['category'], ['user'], ['role'], ['nothing'])
            ->will($this->onConsecutiveCalls(true, false, false, false, false));

        $oFileHandler = $this->getFileHandler();

        $oSetupHandler = new SetupHandler($oWrapper, $oDatabase, $oObjectHandler, $oFileHandler);
        self::assertFalse($oSetupHandler->update());
        self::assertTrue($oSetupHandler->update());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::uninstall()
     */
    public function testUninstall()
    {
        $oWrapper = $this->getWrapper();

        $oWrapper->expects($this->exactly(1))
            ->method('getSites')
            ->will($this->returnValue($this->getSites(1)));

        $oWrapper->expects(($this->exactly(3)))
            ->method('deleteOption')
            ->withConsecutive(
                [Config::ADMIN_OPTIONS_NAME],
                ['uam_version'],
                ['uam_db_version']
            );

        $oWrapper->expects(($this->exactly(1)))
            ->method('switchToBlog')
            ->withConsecutive([1]);

        $oDatabase = $this->getDatabase();

        $oDatabase->expects($this->exactly(1))
            ->method('getCurrentBlogId')
            ->will($this->returnValue(1));

        $oDatabase->expects($this->exactly(1))
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $oDatabase->expects($this->exactly(1))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $oDatabase->expects($this->exactly(1))
            ->method('query')
            ->with(new MatchIgnoreWhitespace(
                'DROP TABLE userGroupTable, userGroupToObjectTable'
            ));

        $oFileHandler = $this->getFileHandler();
        $oFileHandler->expects($this->exactly(1))
            ->method('deleteFileProtection');

        $oSetupHandler = new SetupHandler(
            $oWrapper,
            $oDatabase,
            $this->getObjectHandler(),
            $oFileHandler
        );
        $oSetupHandler->uninstall();
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\SetupHandler\SetupHandler::deactivate()
     */
    public function testDeactivate()
    {
        $oWrapper = $this->getWrapper();
        $oDatabase = $this->getDatabase();
        $oObjectHandler = $this->getObjectHandler();
        $oFileHandler = $this->getFileHandler();

        $oFileHandler->expects($this->exactly(2))
            ->method('deleteFileProtection')
            ->will($this->onConsecutiveCalls(false, true));

        $oSetupHandler = new SetupHandler($oWrapper, $oDatabase, $oObjectHandler, $oFileHandler);
        self::assertFalse($oSetupHandler->deactivate());
        self::assertTrue($oSetupHandler->deactivate());
    }
}
