<?php
/**
 * WidgetFactory.php
 *
 * The WidgetFactory class file.
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

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class WidgetFactory
 *
 * @package UserAccessManager\Widget
 */
class WidgetFactory
{
    /**
     * @var Php
     */
    protected $php;

    /**
     * @var Wordpress
     */
    protected $wordpress;

    /**
     * @var MainConfig
     */
    protected $config;

    /**
     * WidgetFactory constructor.
     *
     * @param Php        $php
     * @param Wordpress  $wordpress
     * @param MainConfig $config
     */
    public function __construct(Php $php, Wordpress $wordpress, MainConfig $config)
    {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->config = $config;
    }

    /**
     * Creates and returns a login widget.
     *
     * @return LoginWidget
     */
    public function createLoginWidget()
    {
        return new LoginWidget($this->php, $this->wordpress, $this->config);
    }
}
