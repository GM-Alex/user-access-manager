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

declare(strict_types=1);

namespace UserAccessManager\Widget;

use UserAccessManager\Config\WordpressConfig;
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
     * @var WordpressConfig
     */
    protected $wordpressConfig;

    /**
     * WidgetFactory constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param WordpressConfig $wordpressConfig
     */
    public function __construct(Php $php, Wordpress $wordpress, WordpressConfig $wordpressConfig)
    {
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->wordpressConfig = $wordpressConfig;
    }

    /**
     * Creates and returns a login widget.
     * @return LoginWidget
     */
    public function createLoginWidget(): LoginWidget
    {
        return new LoginWidget($this->php, $this->wordpress, $this->wordpressConfig);
    }
}
