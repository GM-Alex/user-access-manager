<?php
/**
 * ObjectInformation.php
 *
 * The ObjectInformation class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller\Backend;

use UserAccessManager\UserGroup\AbstractUserGroup;

/**
 * Class ObjectInformation
 *
 * @package UserAccessManager\Controller\Backend
 */
class ObjectInformation
{
    /**
     * @var null|string
     */
    protected $objectType = null;

    /**
     * @var null|string
     */
    protected $objectId = null;

    /**
     * @var int
     */
    protected $userGroupDiff = 0;

    /**
     * @var  AbstractUserGroup[]
     */
    protected $objectUserGroups = [];

    /**
     * Sets the object type.
     *
     * @param string $objectType
     *
     * @return $this
     */
    public function setObjectType($objectType)
    {
        $this->objectType = $objectType;
        return $this;
    }

    /**
     * Returns the current object type.
     *
     * @return null|string
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * Sets the object id.
     *
     * @param $objectId
     *
     * @return $this
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
        return $this;
    }

    /**
     * Returns the current object id.
     *
     * @return null|string
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Sets the user group diff count.
     *
     * @param int $userGroupDiff
     *
     * @return $this
     */
    public function setUserGroupDiff($userGroupDiff)
    {
        $this->userGroupDiff = $userGroupDiff;
        return $this;
    }

    /**
     * Returns the user group count diff.
     *
     * @return int
     */
    public function getUserGroupDiff()
    {
        return $this->userGroupDiff;
    }

    /**
     * Sets the user group diff count.
     *
     * @param $objectUserGroups
     *
     * @return $this
     */
    public function setObjectUserGroups($objectUserGroups)
    {
        $this->objectUserGroups = $objectUserGroups;
        return $this;
    }

    /**
     * Returns the current object user groups.
     *
     * @return AbstractUserGroup[]
     */
    public function getObjectUserGroups()
    {
        return $this->objectUserGroups;
    }
}
