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
 * @version   SVN: $id$
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
        $fileProtectionFactory = new FileProtectionFactory(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig(),
            $this->getUtil()
        );

        self::assertInstanceOf('\UserAccessManager\FileHandler\FileProtectionFactory', $fileProtectionFactory);

        return $fileProtectionFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\FileHandler\FileProtectionFactory::createApacheFileProtection()
     *
     * @param FileProtectionFactory $fileProtectionFactory
     */
    public function testCreateApacheFileProtection(FileProtectionFactory $fileProtectionFactory)
    {
        $apacheFileProtection = $fileProtectionFactory->createApacheFileProtection();
        self::assertInstanceOf('\UserAccessManager\FileHandler\ApacheFileProtection', $apacheFileProtection);
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  \UserAccessManager\FileHandler\FileProtectionFactory::createNginxFileProtection()
     *
     * @param FileProtectionFactory $fileProtectionFactory
     */
    public function testCreateNginxFileProtection(FileProtectionFactory $fileProtectionFactory)
    {
        $nginxFileProtection = $fileProtectionFactory->createNginxFileProtection();
        self::assertInstanceOf('\UserAccessManager\FileHandler\NginxFileProtection', $nginxFileProtection);
    }
}
