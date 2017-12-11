<?php
/**
 * AdminAboutControllerTest.php
 *
 * The AdminAboutControllerTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Unit\Controller\Backend;

use UserAccessManager\Controller\Backend\AboutController;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use VCR\VCR;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class AdminAboutControllerTest
 *
 * @package UserAccessManager\Tests\Unit\Controller\Backend
 * @coversDefaultClass \UserAccessManager\Controller\Backend\AboutController
 */
class AboutControllerTest extends UserAccessManagerTestCase
{
    /**
     * @var array
     */
    private $remoteSupporters = [
        'Luke Crouch',
        'Juan Rodriguez',
        'mkosel',
        'Jan',
        'GeorgWP',
        'Ron Harding',
        'Zina',
        'Erik Franz&eacute;n',
        'Ivan Marevic',
        'J&uuml;rgen Wiesenbauer',
        'Patric Schwarz',
        'Mark LeRoy',
        'Huska',
        'macbidule',
        'Helmut',
        '-sCo-',
        'Hadi Mostafapour',
        'Diego Valobra',
        'PoleeK',
        'Konsult',
        'Mesut Soylu',
        'ranwaldo',
        'Robert Egger',
        'akiko.pusu',
        'r3d pill',
        'michel.weimerskirch',
        'arjenbreur',
        'jpr105',
        'nwoetzel (https://github.com/nwoetzel)'
    ];

    /**
     * @var FileSystem
     */
    private $root;

    /**
     * Setup virtual file system.
     */
    public function setUp()
    {
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();
    }

    /**
     * Tear down virtual file system.
     */
    public function tearDown()
    {
        $this->root->unmount();
    }

    /**
     * @group unit
     */
    public function testCanCreateInstance()
    {
        $aboutController = new AboutController(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig()
        );

        self::assertInstanceOf(AboutController::class, $aboutController);
    }

    /**
     * @group  unit
     * @covers ::getAllSupporters()
     * @covers ::getSpecialThanks()
     * @covers ::getTopSupporters()
     * @covers ::getSupporters()
     * @runInSeparateProcess
     */
    public function testGetSupporters()
    {
        VCR::turnOn();

        $currentTime = 0;

        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(5))
            ->method('currentTime')
            ->will($this->returnCallback(function () use (&$currentTime) {
                return $currentTime;
            }));

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->exactly(6))
            ->method('getRealPath')
            ->will($this->returnValue('vfs://root/'));

        $aboutController = new AboutController(
            $this->getPhp(),
            $wordpress,
            $wordpressConfig
        );

        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('root', new Directory([
            'assets' => new Directory()
        ]));

        VCR::insertCassette('getSupporters');
        self::assertEquals($this->remoteSupporters, $aboutController->getSpecialThanks());

        /**
         * @var File $jsonFile
         */
        $jsonFile = $rootDir->get('root')->get('assets')->get('supporters.json');

        /** @noinspection PhpUnusedLocalVariableInspection */
        $currentTime = $jsonFile->getDateCreated()->getTimestamp();
        $jsonFile->setContent('{}');

        self::setValue($aboutController, 'supporters', null);
        self::assertEquals([], $aboutController->getSpecialThanks());
        self::assertEquals([], $aboutController->getTopSupporters());
        self::assertEquals([], $aboutController->getSupporters());

        $jsonFile->setContent(
            '{
            "special-thanks": ["a", "b"],
            "top-supporters": ["c", "d"],
            "supporters": ["e", "f"]
            }'
        );

        self::setValue($aboutController, 'supporters', null);
        self::assertEquals(['a', 'b'], $aboutController->getSpecialThanks());
        self::assertEquals(['c', 'd'], $aboutController->getTopSupporters());
        self::assertEquals(['e', 'f'], $aboutController->getSupporters());

        self::setValue($aboutController, 'supporters', null);
        self::assertEquals(['a', 'b'], $aboutController->getSpecialThanks());
        self::assertEquals(['c', 'd'], $aboutController->getTopSupporters());
        self::assertEquals(['e', 'f'], $aboutController->getSupporters());

        /** @noinspection PhpUnusedLocalVariableInspection */
        $currentTime = $jsonFile->getDateCreated()->getTimestamp() + 24 * 60 * 60;
        self::setValue($aboutController, 'supporters', null);
        self::assertEquals(['a', 'b'], $aboutController->getSpecialThanks());
        self::assertEquals(['c', 'd'], $aboutController->getTopSupporters());
        self::assertEquals(['e', 'f'], $aboutController->getSupporters());

        /** @noinspection PhpUnusedLocalVariableInspection */
        $currentTime = $jsonFile->getDateCreated()->getTimestamp() + 24 * 60 * 60 + 1;
        self::setValue($aboutController, 'supporters', null);
        self::assertEquals($this->remoteSupporters, $aboutController->getSpecialThanks());
        self::assertEquals([], $aboutController->getTopSupporters());
        self::assertEquals([], $aboutController->getSupporters());

        VCR::eject();
        VCR::turnOff();
    }
}
