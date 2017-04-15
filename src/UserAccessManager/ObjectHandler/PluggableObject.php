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
 * @version   SVN: $id$
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
    private $name;

    /**
     * PluggableObject constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $objectId
     *
     * @return string
     */
    abstract public function getObjectName($objectId);

    /**
     * @param string $objectId
     *
     * @return object|null
     */
    abstract public function getObject($objectId);

    /**
     * @param UserGroup $userGroup
     * @param string    $objectId
     *
     * @return array
     */
    abstract public function getRecursiveMembership(UserGroup $userGroup, $objectId);

    /**
     * @param UserGroup $userGroup
     *
     * @return array
     */
    abstract public function getFullObjects(UserGroup $userGroup);
}
