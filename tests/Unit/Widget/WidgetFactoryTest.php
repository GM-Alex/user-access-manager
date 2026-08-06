<?php

namespace UserAccessManager\Tests\Unit\Widget;

use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;
use UserAccessManager\Widget\LoginWidget;
use UserAccessManager\Widget\WidgetFactory;

/**
 * @coversDefaultClass \UserAccessManager\Widget\WidgetFactory
 */
class WidgetFactoryTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     * @return WidgetFactory
     */
    public function testCanCreateInstance(): WidgetFactory
    {
        $widgetFactory = new WidgetFactory(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getWordpressConfig()
        );

        self::assertInstanceOf(WidgetFactory::class, $widgetFactory);

        return $widgetFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createLoginWidget()
     * @param WidgetFactory $widgetFactory
     */
    public function testCreateLoginWidget(WidgetFactory $widgetFactory)
    {
        self::assertInstanceOf(LoginWidget::class, $widgetFactory->createLoginWidget());
    }
}
