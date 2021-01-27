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

namespace UserAccessManager\Tests\Unit\File;

use UserAccessManager\File\ApacheFileProtection;
use UserAccessManager\File\FileProtectionFactory;
use UserAccessManager\File\NginxFileProtection;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * Class FileProtectionFactoryTest
 *
 * @package UserAccessManager\Tests\Unit\File
 * @coversDefaultClass \UserAccessManager\File\FileProtectionFactory
 */
class FileProtectionFactoryTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     * @return FileProtectionFactory
     */
    public function testCanCreateInstance(): FileProtectionFactory
    {
        $fileProtectionFactory = new FileProtectionFactory(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig(),
            $this->getMainConfig(),
            $this->getUtil()
        );

        self::assertInstanceOf(FileProtectionFactory::class, $fileProtectionFactory);

        return $fileProtectionFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createApacheFileProtection()
     * @param FileProtectionFactory $fileProtectionFactory
     */
    public function testCreateApacheFileProtection(FileProtectionFactory $fileProtectionFactory)
    {
        $apacheFileProtection = $fileProtectionFactory->createApacheFileProtection();
        self::assertInstanceOf(ApacheFileProtection::class, $apacheFileProtection);
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createNginxFileProtection()
     * @param FileProtectionFactory $fileProtectionFactory
     */
    public function testCreateNginxFileProtection(FileProtectionFactory $fileProtectionFactory)
    {
        $nginxFileProtection = $fileProtectionFactory->createNginxFileProtection();
        self::assertInstanceOf(NginxFileProtection::class, $nginxFileProtection);
    }
}
