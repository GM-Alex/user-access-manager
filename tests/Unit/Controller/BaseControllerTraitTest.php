<?php

namespace UserAccessManager\Tests\Unit\Controller;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use stdClass;
use UserAccessManager\Controller\BaseControllerTrait;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use Vfs\FileSystem;
use Vfs\Node\Directory;
use Vfs\Node\File;

/**
 * @coversDefaultClass \UserAccessManager\Controller\BaseControllerTrait
 */
class BaseControllerTraitTest extends UserAccessManagerTestCase
{
    /**
     * @var FileSystem
     */
    private FileSystem $root;

    /**
     * Setup virtual file system.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->root = FileSystem::factory('vfs://');
        $this->root->mount();
    }

    /**
     * Tear down virtual file system.
     */
    protected function tearDown(): void
    {
        $this->root->unmount();
        parent::tearDown();
    }

    /**
     * @return MockObject|BaseControllerTrait
     */
    private function getStub(): MockObject|BaseControllerTrait
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

        $_GET['objectParam'] = new stdClass();
        $_GET['arrayParam'] = [
            'normalKey' => '<script>alert(\'evil\\\Value\');</script>',
            '<script>alert(\'evilKey\');</script>' => 'normalValue',
            'array' => ['a' => '<script>alert(\'otherEvil\');</script>']
        ];
        self::assertEquals(new stdClass(), $stub->getRequestParameter('objectParam'));
        self::assertEquals(
            [
                'normalKey' => '&lt;script&gt;alert(&#039;evil\Value&#039;);&lt;/script&gt;',
                '&lt;script&gt;alert(&#039;evilKey&#039;);&lt;/script&gt;' => 'normalValue',
                'array' => ['a' => '&lt;script&gt;alert(&#039;otherEvil&#039;);&lt;/script&gt;']
            ],
            $stub->getRequestParameter('arrayParam')
        );
    }

    /**
     * Quotes have to be encoded, otherwise a reflected parameter can break out of
     * the surrounding html attribute and add an event handler to the element.
     *
     * @group  unit
     * @covers ::getRequestParameter()
     * @covers ::sanitizeValue()
     */
    public function testGetRequestParameterEncodesQuotes()
    {
        $stub = $this->getStub();

        // WordPress adds the slashes to the request values, so the payload arrives escaped.
        $_GET['attributeBreakout'] = 'x\\" onmouseover=alert(document.domain) y=\\"';
        $_GET['singleQuoted'] = 'x\\\' onmouseover=alert(document.domain) y=\\\'';

        self::assertEquals(
            'x&quot; onmouseover=alert(document.domain) y=&quot;',
            $stub->getRequestParameter('attributeBreakout')
        );
        self::assertEquals(
            'x&#039; onmouseover=alert(document.domain) y=&#039;',
            $stub->getRequestParameter('singleQuoted')
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

        self::assertEquals(
            'https://test.domain?id=&lt;a href=&#039;evil&#039;&gt;evil&lt;/a&gt;',
            $stub->getRequestUrl()
        );

        // The url is echoed into action and href attributes, so quotes must not survive.
        $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?page=uam_settings&x=" onmouseover=alert(1) y="';

        self::assertEquals(
            '/wp-admin/admin.php?page=uam_settings&amp;x=&quot; onmouseover=alert(1) y=&quot;',
            $stub->getRequestUrl()
        );

        unset($_SERVER['REQUEST_URI']);

        self::assertEquals('', $stub->getRequestUrl());
    }

    /**
     * @group   unit
     * @covers  ::render()
     * @covers  ::getIncludeContents()
     * @throws ReflectionException
     */
    public function testRender()
    {
        /**
         * @var Directory $rootDir
         */
        $rootDir = $this->root->get('/');
        $rootDir->add('root', new Directory([
            'src' => new Directory([
                'View' => new Directory([
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
                    throw new Exception('Include file exception');
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
            . 'Error on including content \'vfs://root/src/View/TestView.php\': Include file exception'
        );
    }
}
