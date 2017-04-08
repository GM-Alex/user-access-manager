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
    protected $oWordpress;

    /**
     * @var Database
     */
    protected $oDatabase;

    /**
     * @var Config
     */
    protected $oConfig;

    /**
     * @var Cache
     */
    protected $oCache;

    /**
     * @var Util
     */
    protected $oUtil;

    /**
     * @var ObjectHandler
     */
    protected $oObjectHandler;

    /**
     * UserGroupFactory constructor.
     *
     * @param Wordpress     $oWordpress
     * @param Database      $oDatabase
     * @param Config        $oConfig
     * @param Util          $oUtil
     * @param ObjectHandler $oObjectHandler
     */
    public function __construct(
        Wordpress $oWordpress,
        Database $oDatabase,
        Config $oConfig,
        Util $oUtil,
        ObjectHandler $oObjectHandler
    ) {
        $this->oWordpress = $oWordpress;
        $this->oDatabase = $oDatabase;
        $this->oConfig = $oConfig;
        $this->oUtil = $oUtil;
        $this->oObjectHandler = $oObjectHandler;
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
            $this->oWordpress,
            $this->oDatabase,
            $this->oConfig,
            $this->oUtil,
            $this->oObjectHandler,
            $sId
        );
    }
}
