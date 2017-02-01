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

/**
 * Class FileProtectionFactoryTest
 *
 * @package UserAccessManager\FileHandler
 */
class FileProtectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Wrapper\Wordpress
     */
    private function getWrapper()
    {
        return $this->createMock('\UserAccessManager\Wrapper\Wordpress');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Config\Config
     */
    private function getConfig()
    {
        return $this->createMock('\UserAccessManager\Config\Config');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Util\Util
     */
    private function getUtil()
    {
        return $this->createMock('\UserAccessManager\Util\Util');
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\FileHandler\FileProtectionFactory::__construct()
     *
     * @return FileProtectionFactory
     */
    public function testCanCreateInstance()
    {
        $oFileProtectionFactory = new FileProtectionFactory(
            $this->getWrapper(),
            $this->getConfig(),
            $this->getUtil()
        );
        
        self::assertInstanceOf('\UserAccessManager\FileHandler\FileProtectionFactory', $oFileProtectionFactory);

        return $oFileProtectionFactory;
    }

    /**
     * @covers \UserAccessManager\FileHandler\FileProtectionFactory::createApacheFileProtection()
     * @depends testCanCreateInstance
     *
     * @param FileProtectionFactory $oFileProtectionFactory
     */
    public function testCreateApacheFileProtection(FileProtectionFactory $oFileProtectionFactory)
    {
        $oApacheFileProtection = $oFileProtectionFactory->createApacheFileProtection();
        self::assertInstanceOf('\UserAccessManager\FileHandler\ApacheFileProtection', $oApacheFileProtection);
    }

    /**
     * @covers \UserAccessManager\FileHandler\FileProtectionFactory::createNginxFileProtection()
     * @depends testCanCreateInstance
     *
     * @param FileProtectionFactory $oFileProtectionFactory
     */
    public function testCreateNginxFileProtection(FileProtectionFactory $oFileProtectionFactory)
    {
        $oNginxFileProtection = $oFileProtectionFactory->createNginxFileProtection();
        self::assertInstanceOf('\UserAccessManager\FileHandler\NginxFileProtection', $oNginxFileProtection);
    }
}
