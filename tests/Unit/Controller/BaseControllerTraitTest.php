<?php
/**
 * BaseControllerTraitTest.php
 *
 * The BaseControllerTraitTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Unit\Controller;

use UserAccessManager\Controller\BaseControllerTrait;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * Class BaseControllerTraitTest
 *
 * @package UserAccessManager\Tests\Unit\Controller
 * @coversDefaultClass \UserAccessManager\Controller\BaseControllerTrait
 */
class BaseControllerTraitTest extends UserAccessManagerTestCase
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
     * @return \PHPUnit_Framework_MockObject_MockObject|BaseControllerTrait
     */
    private function getStub()
    {
        return $this->getMockForTrait(BaseControllerTrait::class);
    }

    /**
     * @group  unit
     * @covers ::getRequestParameter()
     * @covers ::sanitizeValue()
     */
    public function testGetRequestParameter()
    {
        $stub = $this->getStub();

        $_POST['postParam'] = 'postValue';
        $_GET['postParam'] = 'getValue';
        $_GET['getParam'] = 'getValue';

        self::assertEquals('postValue', $stub->getRequestParameter('postParam', 'default'));
        self::assertEquals('getValue', $stub->getRequestParameter('getParam', 'default'));
        self::assertEquals('default', $stub->getRequestParameter('invalid', 'default'));
        self::assertNull($stub->getRequestParameter('invalid'));

        $_GET['objectParam'] = new \stdClass();
        $_GET['arrayParam'] = [
            'normalKey' => '<script>alert(\'evil\\\Value\');</script>',
            '<script>alert(\'evilKey\');</script>' => 'normalValue',
            'array' => ['a' => '<script>alert(\'otherEvil\');</script>']
        ];
        self::assertEquals(new \stdClass(), $stub->getRequestParameter('objectParam'));
        self::assertEquals(
            [
                'normalKey' => '&lt;script&gt;alert(\'evil\Value\');&lt;/script&gt;',
                '&lt;script&gt;alert(\'evilKey\');&lt;/script&gt;' => 'normalValue',
                'array' => ['a' => '&lt;script&gt;alert(\'otherEvil\');&lt;/script&gt;']
            ],
            $stub->getRequestParameter('arrayParam')
        );
    }

    /**
     * @group  unit
     * @covers ::getRequestUrl()
     */
    public function testGetRequestUrl()
    {
        $stub = $this->getStub();

        $_SERVER['REQUEST_URI'] = 'https://test.domain?id=<a href=\'evil\'>evil</a>';

        self::assertEquals('https://test.domain?id=&lt;a href=\'evil\'&gt;evil&lt;/a&gt;', $stub->getRequestUrl());
    }

    /**
     * @group   unit
     * @covers  ::render()
     * @covers  ::getIncludeContents()
     */
    public function testRender()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('root', new Directory([
            'src' => new Directory([
                'View'  => new Directory([
                    'TestView.php' => new File('<?php echo \'testContent\';')
                ])
            ])
        ]));

        $php = $this->getPhp();

        $wordpressConfig = $this->getWordpressConfig();
        $wordpressConfig->expects($this->exactly(2))
            ->method('getRealPath')
            ->will($this->returnValue('vfs://root/'));

        $stub = $this->getStub();

        $stub->expects($this->any())
            ->method('getPhp')
            ->will($this->returnValue($php));

        $stub->expects($this->any())
            ->method('getWordpressConfig')
            ->will($this->returnValue($wordpressConfig));

        $throwException = false;

        $php->expects($this->exactly(2))
            ->method('includeFile')
            ->with($stub, 'vfs://root/src/View/TestView.php')
            ->will($this->returnCallback(function () use (&$throwException) {
                if ($throwException === true) {
                    throw new \Exception('Include file exception');
                }

                echo 'testContent';
            }));

        self::setValue($stub, 'template', 'TestView.php');

        $stub->render();

        /** @noinspection PhpUnusedLocalVariableInspection */
        $throwException = true;
        $stub->render();

        self::expectOutputString(
            'testContent'
            .'Error on including content \'vfs://root/src/View/TestView.php\': Include file exception'
        );
    }
}
