<?php
/**
 * DynamicUserGroup.php
 *
 * The DynamicUserGroup class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\UserGroup;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class DynamicUserGroup
 *
 * @package UserAccessManager\UserGroup
 */
class DynamicUserGroup extends AbstractUserGroup
{
    /**
     * DynamicUserGroup constructor.
     *
     * @param Php           $php
     * @param Wordpress     $wordpress
     * @param Database      $database
     * @param MainConfig    $config
     * @param Util          $util
     * @param ObjectHandler $objectHandler
     * @param string        $type
     * @param int           $id
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Database $database,
        MainConfig $config,
        Util $util,
        ObjectHandler $objectHandler,
        $type,
        $id
    ) {
        parent::__construct($php, $wordpress, $database, $config, $util, $objectHandler);

        $this->type = $type;
        $this->id = $id;
    }
}
