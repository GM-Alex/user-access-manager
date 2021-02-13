<?php
/**
 * LoginWidgetTest.php
 *
 * The LoginWidgetTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\Widget;

use ReflectionException;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\Widget\LoginWidget;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class LoginWidgetTest
 *
 * @package UserAccessManager\Tests\Unit\Widget
 * @coversDefaultClass \UserAccessManager\Widget\LoginWidget
 */
class LoginWidgetTest extends UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private $root;

    /**
     * Setup virtual file system.
     */
    protected function setUp(): void
    {
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();
    }

    /**
     * Tear down virtual file system.
     */
    protected function tearDown(): void
    {
        $this->root->unmount();
    }

    /**
     * @group  unit
     * @covers ::__construct()
     * @throws ReflectionException
     */
    public function testCanCreateInstance()
    {
        $loginWidget = new LoginWidget(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig()
        );

        self::assertInstanceOf(LoginWidget::class, $loginWidget);
        self::assertEquals($this->getPhp(), $this->callMethod($loginWidget, 'getPhp'));
        self::assertEquals($this->getWordpress(), $this->callMethod($loginWidget, 'getWordpress'));
        self::assertEquals($this->getWordpressConfig(), $this->callMethod($loginWidget, 'getWordpressConfig'));

        self::assertEquals('uam_login_widget', $loginWidget->id_base);
        self::assertEquals('UAM login widget|user-access-manager', $loginWidget->name);
        self::assertEquals('widget_uam_login_widget', $loginWidget->option_name);
        self::assertEquals(
            [
                'classname' => 'widget_uam_login_widget',
                'customize_selective_refresh' => false,
                'description' => 'User Access Manager login widget for users.|user-access-manager'
            ],
            $loginWidget->widget_options
        );
        self::assertEquals(['id_base' => 'uam_login_widget'], $loginWidget->control_options);
    }

    /**
     * @group  unit
     * @covers ::getPhp()
     * @covers ::getWordpress()
     * @covers ::getWordpressConfig()
     * @throws ReflectionException
     */
    public function testSimpleGetters()
    {
        $loginWidget = new LoginWidget(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig()
        );

        self::assertEquals($this->getPhp(), self::callMethod($loginWidget, 'getPhp'));
        self::assertEquals($this->getWordpress(), self::callMethod($loginWidget, 'getWordpress'));
        self::assertEquals($this->getWordpressConfig(), self::callMethod($loginWidget, 'getWordpressConfig'));
    }

    /**
     * @group  unit
     * @covers ::widget()
     */
    public function testWidget()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('root', new Directory([
            'src' => new Directory([
                'View' => new Directory([
                    'LoginWidget.php' => new File('<?php echo \'testContent\';')
                ])
            ])
        ]));

        $php = $this->getPhp();

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('vfs://root/'));

        $loginWidget = new LoginWidget(
            $php,
            $this->getWordpress(),
            $wordpressConfig
        );

        $php->expects($this->any())
            ->method('includeFile')
            ->with($loginWidget, 'vfs://root/src/View/LoginWidget.php')
            ->will($this->returnCallback(function () {
                echo 'loginWidgetContent';
            }));

        self::expectOutputString('loginWidgetContent');

        $loginWidget->widget([], []);
    }
}
