<?php
/**
 * UserGroupFactory.php
 *
 * The UserGroupFactory class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\UserGroup;

use UserAccessManager\Cache\Cache;
use UserAccessManager\Config\Config;
use UserAccessManager\Database\Database;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class UserGroupFactory
 *
 * @package UserAccessManager\UserGroup
 */
class UserGroupFactory
{
    /**
     * @var Wordpress
     */
    protected $Wordpress;

    /**
     * @var Database
     */
    protected $Database;

    /**
     * @var Config
     */
    protected $Config;

    /**
     * @var Cache
     */
    protected $Cache;

    /**
     * @var Util
     */
    protected $Util;

    /**
     * @var ObjectHandler
     */
    protected $ObjectHandler;

    /**
     * UserGroupFactory constructor.
     *
     * @param Wordpress     $Wordpress
     * @param Database      $Database
     * @param Config        $Config
     * @param Util          $Util
     * @param ObjectHandler $ObjectHandler
     */
    public function __construct(
        Wordpress $Wordpress,
        Database $Database,
        Config $Config,
        Util $Util,
        ObjectHandler $ObjectHandler
    ) {
        $this->Wordpress = $Wordpress;
        $this->Database = $Database;
        $this->Config = $Config;
        $this->Util = $Util;
        $this->ObjectHandler = $ObjectHandler;
    }

    /**
     * Creates a new user group object.
     *
     * @param string $sId
     *
     * @return UserGroup
     */
    public function createUserGroup($sId = null)
    {
        return new UserGroup(
            $this->Wordpress,
            $this->Database,
            $this->Config,
            $this->Util,
            $this->ObjectHandler,
            $sId
        );
    }
}
