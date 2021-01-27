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

declare(strict_types=1);

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
     * @param string $objectType
     * @return $this
     */
    public function setObjectType(string $objectType): ObjectInformation
    {
        $this->objectType = $objectType;
        return $this;
    }

    /**
     * Returns the current object type.
     * @return null|string
     */
    public function getObjectType(): ?string
    {
        return $this->objectType;
    }

    /**
     * Sets the object id.
     * @param $objectId
     * @return $this
     */
    public function setObjectId($objectId): ObjectInformation
    {
        $this->objectId = $objectId;
        return $this;
    }

    /**
     * Returns the current object id.
     * @return int|string|null
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Sets the user group diff count.
     * @param int $userGroupDiff
     * @return $this
     */
    public function setUserGroupDiff(int $userGroupDiff): ObjectInformation
    {
        $this->userGroupDiff = $userGroupDiff;
        return $this;
    }

    /**
     * Returns the user group count diff.
     * @return int
     */
    public function getUserGroupDiff(): int
    {
        return $this->userGroupDiff;
    }

    /**
     * Sets the user group diff count.
     * @param $objectUserGroups
     * @return $this
     */
    public function setObjectUserGroups($objectUserGroups): ObjectInformation
    {
        $this->objectUserGroups = $objectUserGroups;
        return $this;
    }

    /**
     * Returns the current object user groups.
     * @return AbstractUserGroup[]
     */
    public function getObjectUserGroups(): array
    {
        return $this->objectUserGroups;
    }
}
