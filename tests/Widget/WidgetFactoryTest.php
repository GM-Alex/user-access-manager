<?php
/**
 * WidgetFactoryTest.php
 *
 * The WidgetFactoryTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Tests\Widget;

use UserAccessManager\Tests\UserAccessManagerTestCase;
use UserAccessManager\Widget\LoginWidget;
use UserAccessManager\Widget\WidgetFactory;

/**
 * Class WidgetFactoryTest
 *
 * @package UserAccessManager\Widget
 * @coversDefaultClass \UserAccessManager\Widget\WidgetFactory
 */
class WidgetFactoryTest extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     *
     * @return WidgetFactory
     */
    public function testCanCreateInstance()
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
     *
     * @param WidgetFactory $widgetFactory
     */
    public function testCreateLoginWidget(WidgetFactory $widgetFactory)
    {
        self::assertInstanceOf(LoginWidget::class, $widgetFactory->createLoginWidget());
    }
}
