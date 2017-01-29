<?php
/**
 * ConfigTest.php
 *
 * The ConfigTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Config;

/**
 * Class ConfigTest
 *
 * @package UserAccessManager\Config
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \UserAccessManager\Wrapper\Wordpress $oWrapper
     */
    protected $_oDefaultWrapper;

    /**
     * @var \UserAccessManager\Config\ConfigParameterFactory $oConfigParameterFactory
     */
    protected $_oDefaultConfigParameterFactory;

    /**
     * Create default mocked objects.
     */
    public function setUp()
    {
        $this->_oDefaultWrapper = $this->createMock('\UserAccessManager\Wrapper\Wordpress');
        $this->_oDefaultConfigParameterFactory = $this->createMock('\UserAccessManager\Config\ConfigParameterFactory');
    }

    /**
     * @group unit
     * @covers  \UserAccessManager\Config\Config::__construct()
     */
    public function testCanCreateInstance()
    {
        $oConfig = new Config($this->_oDefaultWrapper, $this->_oDefaultConfigParameterFactory, 'baseFile');
        self::assertInstanceOf('\UserAccessManager\Config\Config', $oConfig);
    }

    /**
     * @group unit
     * @covers  \UserAccessManager\Config\Config::atAdminPanel()
     */
    public function testAtAdminPanel()
    {
        /**
         * @var \UserAccessManager\Wrapper\Wordpress $oWrapper
         */
        $oWrapper = clone $this->_oDefaultWrapper;
        $oWrapper->expects($this->any())
            ->method('isAdmin')
            ->will($this->onConsecutiveCalls(true, false));

        $oConfig = new Config($oWrapper, $this->_oDefaultConfigParameterFactory, 'baseFile');
        self::assertEquals(true, $oConfig->atAdminPanel());
        self::assertEquals(false, $oConfig->atAdminPanel());
    }

    /**
     * @group unit
     * @covers  \UserAccessManager\Config\Config::getUploadDirectory()
     */
    public function testGetUploadDirectory()
    {
        /**
         * @var \UserAccessManager\Wrapper\Wordpress $oWrapper
         */
        $oWrapper = clone $this->_oDefaultWrapper;
        $oWrapper->expects($this->any())
            ->method('getUploadDir')
            ->will(
                $this->onConsecutiveCalls(
                    array(
                        'error' => 'error',
                        'basedir' => 'baseDir'
                    ),
                    array(
                        'error' => null,
                        'basedir' => 'baseDir'
                    )
                )
            );

        $oConfig = new Config($oWrapper, $this->_oDefaultConfigParameterFactory, 'baseFile');
        self::assertEquals(null, $oConfig->getUploadDirectory());
        self::assertEquals('baseDir/', $oConfig->getUploadDirectory());
    }

    /**
     * @group unit
     * @covers  \UserAccessManager\Config\Config::getWpOption()
     */
    public function testGetWpOption()
    {
        /**
         * @var \UserAccessManager\Wrapper\Wordpress $oWrapper
         */
        $oWrapper = clone $this->_oDefaultWrapper;
        $oWrapper->expects($this->any())
            ->method('getOption')
            ->will($this->onConsecutiveCalls('optionValueOne', 'optionValueTwo'));

        $oConfig = new Config($oWrapper, $this->_oDefaultConfigParameterFactory, 'baseFile');
        $mOptionOne = $oConfig->getWpOption('optionOne');
        $mOptionOneAgain = $oConfig->getWpOption('optionOne');

        self::assertEquals('optionValueOne', $mOptionOne);
        self::assertEquals('optionValueOne', $mOptionOneAgain);

        $mOptionTwo = $oConfig->getWpOption('optionTwo');
        self::assertEquals('optionValueTwo', $mOptionTwo);

        $mOptionTwo = $oConfig->getWpOption('optionNotExisting');
        self::assertEquals(null, $mOptionTwo);
    }

    /**
     * @group unit
     * @covers  \UserAccessManager\Config\Config::getConfigParameters()
     */
    public function testGetConfigParameters()
    {
        /**
         * @var \UserAccessManager\Wrapper\Wordpress $oWrapper
         */
        $oWrapper = clone $this->_oDefaultWrapper;
        $oWrapper->expects($this->any())
            ->method('getOption')
            ->will($this->onConsecutiveCalls(
                null,
                array())
            );

        $oConfig = new Config($oWrapper, $this->_oDefaultConfigParameterFactory, 'baseFile');

        print_r($oConfig->getConfigParameters());
    }
}
