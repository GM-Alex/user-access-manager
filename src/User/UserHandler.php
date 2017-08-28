<?php
/**
 * UserHandler.php
 *
 * The UserHandler class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\User;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\UserGroup;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class UserHandler
 *
 * @package UserAccessManager\UserHandler
 */
class UserHandler
{
    const MANAGE_USER_GROUPS_CAPABILITY = 'manage_user_groups';

    /**
     * @var Wordpress
     */
    private $wordpress;

    /**
     * @var MainConfig
     */
    private $config;

    /**
     * @var Database
     */
    private $database;

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * The constructor
     *
     * @param Wordpress        $wordpress
     * @param MainConfig       $config
     * @param Database         $database
     * @param ObjectHandler    $objectHandler
     */
    public function __construct(
        Wordpress $wordpress,
        MainConfig $config,
        Database $database,
        ObjectHandler $objectHandler
    ) {
        $this->wordpress = $wordpress;
        $this->config = $config;
        $this->database = $database;
        $this->objectHandler = $objectHandler;
    }

    /**
     * Converts the ip to an integer.
     *
     * @param string $ip
     *
     * @return string|false
     */
    private function calculateIp($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return base_convert(ip2long($ip), 10, 2);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return false;
        }

        $packedIp = inet_pton($ip);
        $bits = 15; // 16 x 8 bit = 128bit (ipv6)
        $binaryIp = '';

        while ($bits >= 0) {
            $binaryIp = sprintf('%08b', (ord($packedIp[$bits]))).$binaryIp;
            $bits--;
        }

        return $binaryIp;
    }

    /**
     * Checks if the given ip matches with the range.
     *
     * @param string $currentIp The ip of the current user.
     * @param array  $ipRanges  The ip ranges.
     *
     * @return bool
     */
    public function isIpInRange($currentIp, array $ipRanges)
    {
        $currentIp = $this->calculateIp($currentIp);

        if ($currentIp !== false) {
            foreach ($ipRanges as $ipRange) {
                $ipRange = explode('-', $ipRange);
                $rangeBegin = $ipRange[0];
                $rangeEnd = isset($ipRange[1]) ? $ipRange[1] : $ipRange[0];
                $rangeBegin = $this->calculateIp($rangeBegin);
                $rangeEnd = $this->calculateIp($rangeEnd);

                if ($rangeBegin !== false && $rangeEnd !== false
                    && $rangeBegin <= $currentIp && $currentIp <= $rangeEnd
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return the role of the user.
     *
     * @param \WP_User|false $user The user.
     *
     * @return array
     */
    public function getUserRole($user)
    {
        if ($user instanceof \WP_User && isset($user->{$this->database->getPrefix().'capabilities'}) === true) {
            $capabilities = (array)$user->{$this->database->getPrefix().'capabilities'};
        } else {
            $capabilities = [];
        }

        return (count($capabilities) > 0) ? array_keys($capabilities) : [UserGroup::NONE_ROLE];
    }

    /**
     * Checks the user access by user level.
     *
     * @param bool|string $allowedCapability If set check also for the capability.
     *
     * @return bool
     */
    public function checkUserAccess($allowedCapability = false)
    {
        $currentUser = $this->wordpress->getCurrentUser();

        if ($this->wordpress->isSuperAdmin($currentUser->ID) === true
            || $allowedCapability !== false && $currentUser->has_cap($allowedCapability) === true
        ) {
            return true;
        }

        $roles = $this->getUserRole($currentUser);
        $rolesMap = array_flip($roles);

        $orderedRoles = [UserGroup::NONE_ROLE, 'subscriber', 'contributor', 'author', 'editor', 'administrator'];
        $orderedRolesMap = array_flip($orderedRoles);

        $userRoles = array_intersect_key($orderedRolesMap, $rolesMap);
        $rightsLevel = (count($userRoles) > 0) ? end($userRoles) : -1;
        $fullAccessRole = $this->config->getFullAccessRole();

        return (isset($orderedRolesMap[$fullAccessRole]) === true && $rightsLevel >= $orderedRolesMap[$fullAccessRole]
            || isset($rolesMap['administrator']) === true
        );
    }

    /**
     * Checks if the user is an admin user
     *
     * @param integer $userId The user id.
     *
     * @return bool
     */
    public function userIsAdmin($userId)
    {
        $user = $this->objectHandler->getUser($userId);
        $roles = $this->getUserRole($user);
        $rolesMap = array_flip($roles);

        return (isset($rolesMap['administrator']) === true || $this->wordpress->isSuperAdmin($userId) === true);
    }
}