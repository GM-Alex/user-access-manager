<?php
/**
 * PluggableObject.php
 *
 * The PluggableObject unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\ObjectHandler;
use UserAccessManager\UserGroup\UserGroup;

/**
 * Class PluggableObject
 *
 * @package UserAccessManager\ObjectHandler
 */
abstract class PluggableObject
{
    /**
     * @var string
     */
    protected $_sName;

    public function __construct($sName, $sReference)
    {
        $this->_sName = $sName;
        $this->_sReference = $sReference;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_sName;
    }

    /**
     * @param UserGroup $oUserGroup
     * @param string    $sObjectId
     *
     * @return array
     */
    abstract public function getRecursiveMembership(UserGroup $oUserGroup, $sObjectId);

    /**
     * @param UserGroup $oUserGroup
     *
     * @return array
     */
    abstract public function getFullObjects(UserGroup $oUserGroup);
}