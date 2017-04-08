<?php
/**
 * FileProtectionFactoryTest.php
 *
 * The FileProtectionFactoryTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\FileHandler;

use UserAccessManager\UserAccessManagerTestCase;

/**
 * Class FileProtectionFactoryTest
 *
 * @package UserAccessManager\FileHandler
 */
class FileProtectionFactoryTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\FileHandler\FileProtectionFactory::__construct()
     *
     * @return FileProtectionFactory
     */
    public function testCanCreateInstance()
    {
        $FileProtectionFactory = new FileProtectionFactory(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getConfig(),
            $this->getUtil()
        );

        self::assertInstanceOf('\UserAccessManager\FileHandler\FileProtectionFactory', $FileProtectionFactory);

        return $FileProtectionFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\FileHandler\FileProtectionFactory::createApacheFileProtection()
     *
     * @param FileProtectionFactory $FileProtectionFactory
     */
    public function testCreateApacheFileProtection(FileProtectionFactory $FileProtectionFactory)
    {
        $ApacheFileProtection = $FileProtectionFactory->createApacheFileProtection();
        self::assertInstanceOf('\UserAccessManager\FileHandler\ApacheFileProtection', $ApacheFileProtection);
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\FileHandler\FileProtectionFactory::createNginxFileProtection()
     *
     * @param FileProtectionFactory $FileProtectionFactory
     */
    public function testCreateNginxFileProtection(FileProtectionFactory $FileProtectionFactory)
    {
        $NginxFileProtection = $FileProtectionFactory->createNginxFileProtection();
        self::assertInstanceOf('\UserAccessManager\FileHandler\NginxFileProtection', $NginxFileProtection);
    }
}
