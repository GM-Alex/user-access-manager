<?php

namespace UserAccessManager\Tests\Unit\Config;

use ReflectionException;
use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * @coversDefaultClass \UserAccessManager\Config\WordpressConfig
 */
class WordpressConfigTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $wordpressConfig = new WordpressConfig($this->getWordpress(), 'baseFile');

        self::assertInstanceOf(WordpressConfig::class, $wordpressConfig);
    }

    /**
     * @group   unit
     * @covers  ::atAdminPanel()
     */
    public function testAtAdminPanel()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('isAdmin')
            ->will($this->onConsecutiveCalls(true, false));

        $wordpressConfig = new WordpressConfig($wordpress, 'baseFile');

        self::assertTrue($wordpressConfig->atAdminPanel());
        self::assertFalse($wordpressConfig->atAdminPanel());
    }

    /**
     * @group  unit
     * @covers ::isPermalinksActive()
     * @throws ReflectionException
     */
    public function testIsPermalinksActive()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('getOption')
            ->will($this->onConsecutiveCalls('aaa', ''));

        $wordpressConfig = new WordpressConfig($wordpress, 'baseFile');

        self::assertTrue($wordpressConfig->isPermalinksActive());
        self::assertTrue($wordpressConfig->isPermalinksActive());
        self::setValue($wordpressConfig, 'isPermalinksActive', null);
        self::assertFalse($wordpressConfig->isPermalinksActive());
    }

    /**
     * @group  unit
     * @covers ::getUploadDirectory()
     */
    public function testGetUploadDirectory()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('getUploadDir')
            ->will(
                $this->onConsecutiveCalls(
                    [
                        'error' => 'error',
                        'basedir' => 'baseDir'
                    ],
                    [
                        'error' => null,
                        'basedir' => 'baseDir'
                    ]
                )
            );

        $wordpressConfig = new WordpressConfig($wordpress, 'baseFile');

        self::assertEquals(null, $wordpressConfig->getUploadDirectory());
        self::assertEquals('baseDir/', $wordpressConfig->getUploadDirectory());
    }

    /**
     * @group  unit
     * @covers ::getMimeTypes()
     */
    public function testGetMimeTypes()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->exactly(2))
            ->method('getAllowedMimeTypes')
            ->will(
                $this->onConsecutiveCalls(
                    ['a|b' => 'firstType', 'c' => 'secondType'],
                    ['c|b' => 'firstType', 'a' => 'secondType']
                )
            );

        $wordpressConfig = new WordpressConfig($wordpress, 'baseFile');

        self::assertEquals(
            ['a' => 'firstType', 'b' => 'firstType', 'c' => 'secondType'],
            $wordpressConfig->getMimeTypes()
        );
        self::assertEquals(
            ['a' => 'firstType', 'b' => 'firstType', 'c' => 'secondType'],
            $wordpressConfig->getMimeTypes()
        );

        $wordpressConfig = new WordpressConfig($wordpress, 'baseFile');

        self::assertEquals(
            ['c' => 'firstType', 'b' => 'firstType', 'a' => 'secondType'],
            $wordpressConfig->getMimeTypes()
        );
    }

    /**
     * @group  unit
     * @covers ::getUrlPath()
     */
    public function testGetUrlPath()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('pluginsUrl')
            ->will($this->returnValue('pluginsUrl'));

        $wordpressConfig = new WordpressConfig($wordpress, 'baseFile');

        self::assertEquals(
            'pluginsUrl/',
            $wordpressConfig->getUrlPath()
        );
    }

    /**
     * @group  unit
     * @covers ::getRealPath()
     */
    public function testGetRealPath()
    {
        $wordpress = $this->getWordpress();
        $wordpress->expects($this->once())
            ->method('getPluginDir')
            ->will($this->returnValue('pluginDir'));
        $wordpress->expects($this->once())
            ->method('pluginBasename')
            ->will($this->returnValue('pluginBasename'));

        $wordpressConfig = new WordpressConfig($wordpress, 'baseFile');

        self::assertEquals(
            'pluginDir' . DIRECTORY_SEPARATOR . 'pluginBasename' . DIRECTORY_SEPARATOR,
            $wordpressConfig->getRealPath()
        );
    }
}
