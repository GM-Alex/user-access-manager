<?php
/**
 * Config.php
 *
 * The ConfigFactory class file.
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

namespace UserAccessManager\Config;

use UserAccessManager\Wrapper\Wordpress;

/**
 * Class ConfigFactory
 *
 * @package UserAccessManager\Config
 */
class ConfigFactory
{
    private $wordpress;

    /**
     * ConfigFactory constructor.
     * @param Wordpress $wordpress
     */
    public function __construct(Wordpress $wordpress)
    {
        $this->wordpress = $wordpress;
    }

    /**
     * Returns a new configuration.
     * @param string $key
     * @return Config
     */
    public function createConfig(string $key): Config
    {
        return new Config($this->wordpress, $key);
    }
}
