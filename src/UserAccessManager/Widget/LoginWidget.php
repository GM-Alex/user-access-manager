<?php
/**
 * LoginWidget.php
 *
 * The LoginWidget class file.
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

use UserAccessManager\Config\Config;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Controller\BaseControllerTrait;
use UserAccessManager\Controller\LoginControllerTrait;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class LoginWidget
 *
 * @package UserAccessManager\Widget
 */
class LoginWidget extends \WP_Widget
{
    use LoginControllerTrait;

    const WIDGET_ID = 'uam_login_widget';

    /**
     * @var Php
     */
    protected $php;

    /**
     * @var Wordpress
     */
    protected $wordpress;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var null|string
     */
    protected $template = null;

    /**
     * LoginWidget constructor.
     *
     * @param Php        $php
     * @param Wordpress  $wordpress
     * @param MainConfig $config
     */
    public function __construct(Php $php, Wordpress $wordpress, MainConfig $config)
    {
        $this->template = 'LoginWidget.php';
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->config = $config;

        parent::__construct(
            self::WIDGET_ID,
            TXT_UAM_LOGIN_WIDGET_TITLE,
            ['description' => TXT_UAM_LOGIN_WIDGET_DESC]
        );
    }

    /**
     * @param array $args
     * @param array $instance
     */
    public function widget($args, $instance)
    {
        $this->render();
    }
}
