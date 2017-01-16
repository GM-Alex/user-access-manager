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
use UserAccessManager\AccessHandler\AccessHandler;
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
    protected $_oWrapper;

    /**
     * @var Database
     */
    protected $_oDatabase;

    /**
     * @var Config
     */
    protected $_oConfig;

    /**
     * @var Cache
     */
    protected $_oCache;

    /**
     * @var Util
     */
    protected $_oUtil;

    /**
     * @var ObjectHandler
     */
    protected $_oObjectHandler;

    /**
     * UserGroupFactory constructor.
     *
     * @param Wordpress     $oWrapper
     * @param Database      $oDatabase
     * @param Config        $oConfig
     * @param Cache         $oCache
     * @param Util          $oUtil
     * @param ObjectHandler $oObjectHandler
     */
    public function __construct(
        Wordpress $oWrapper,
        Database $oDatabase,
        Config $oConfig,
        Cache $oCache,
        Util $oUtil,
        ObjectHandler $oObjectHandler
    )
    {
        $this->_oWrapper = $oWrapper;
        $this->_oDatabase = $oDatabase;
        $this->_oConfig = $oConfig;
        $this->_oCache = $oCache;
        $this->_oUtil = $oUtil;
        $this->_oObjectHandler = $oObjectHandler;
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
            $this->_oWrapper,
            $this->_oDatabase,
            $this->_oConfig,
            $this->_oCache,
            $this->_oUtil,
            $this->_oObjectHandler,
            $sId
        );
    }
}