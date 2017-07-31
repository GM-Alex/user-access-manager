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
namespace UserAccessManager\Widget;

use UserAccessManager\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class LoginWidgetTest
 *
 * @package UserAccessManager\Widget
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
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $loginWidget = new LoginWidget(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getMainConfig()
        );

        self::assertInstanceOf(LoginWidget::class, $loginWidget);
        self::assertAttributeEquals($this->getPhp(), 'php', $loginWidget);
        self::assertAttributeEquals($this->getWordpress(), 'wordpress', $loginWidget);
        self::assertAttributeEquals($this->getMainConfig(), 'config', $loginWidget);

        self::assertAttributeEquals('uam_login_widget', 'id_base', $loginWidget);
        self::assertAttributeEquals('UAM login widget|user-access-manager', 'name', $loginWidget);
        self::assertAttributeEquals('widget_uam_login_widget', 'option_name', $loginWidget);
        self::assertAttributeEquals(
            [
                'classname' => 'widget_uam_login_widget',
                'customize_selective_refresh' => false,
                'description' => 'User Access Manager login widget for users.|user-access-manager'
            ],
            'widget_options',
            $loginWidget
        );
        self::assertAttributeEquals(['id_base' => 'uam_login_widget'], 'control_options', $loginWidget);
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
        $rootDir->add('src', new Directory([
            'UserAccessManager'  => new Directory([
                'View'  => new Directory([
                    'LoginWidget.php' => new File('<?php echo \'testContent\';')
                ])
            ])
        ]));

        $php = $this->getPhp();

        $config = $this->getMainConfig();
        $config->expects($this->once())
            ->method('getRealPath')
            ->will($this->returnValue('vfs:/'));

        $loginWidget = new LoginWidget(
            $php,
            $this->getWordpress(),
            $config
        );

        $php->expects($this->any())
            ->method('includeFile')
            ->with($loginWidget, 'vfs://src/UserAccessManager/View/LoginWidget.php')
            ->will($this->returnCallback(function () {
                echo 'loginWidgetContent';
            }));

        self::expectOutputString('loginWidgetContent');

        $loginWidget->widget([], []);
    }
}
