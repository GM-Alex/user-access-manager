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

declare(strict_types=1);

namespace UserAccessManager\Widget;

use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\BaseControllerTrait;
use UserAccessManager\Controller\Frontend\LoginControllerTrait;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;
use WP_Widget;

/**
 * Class LoginWidget
 *
 * @package UserAccessManager\Widget
 */
class LoginWidget extends WP_Widget
{
    use BaseControllerTrait;
    use LoginControllerTrait;

    const WIDGET_ID = 'uam_login_widget';

    /**
     * @var Php
     */
    private $php;

    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var WordpressConfig
     */
    private $wordpressConfig;

    /**
     * LoginWidget constructor.
     * @param Php $php
     * @param Wordpress $wordpress
     * @param WordpressConfig $wordpressConfig
     */
    public function __construct(Php $php, Wordpress $wordpress, WordpressConfig $wordpressConfig)
    {
        $this->template = 'LoginWidget.php';
        $this->php = $php;
        $this->wordpress = $wordpress;
        $this->wordpressConfig = $wordpressConfig;

        parent::__construct(
            self::WIDGET_ID,
            TXT_UAM_LOGIN_WIDGET_TITLE,
            ['description' => TXT_UAM_LOGIN_WIDGET_DESC]
        );
    }

    /**
     * @return Php
     */
    protected function getPhp(): Php
    {
        return $this->php;
    }

    /**
     * @return Wordpress
     */
    protected function getWordpress(): Wordpress
    {
        return $this->wordpress;
    }

    /**
     * @return WordpressConfig
     */
    protected function getWordpressConfig(): WordpressConfig
    {
        return $this->wordpressConfig;
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
