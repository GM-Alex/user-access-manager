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

declare(strict_types=1);

namespace UserAccessManager\User;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\Wrapper\Wordpress;
use WP_User;

/**
 * Class UserHandler
 *
 * @package UserAccessManager\UserHandler
 */
class UserHandler
{
    public const MANAGE_USER_GROUPS_CAPABILITY = 'manage_user_groups';

    public function __construct(
        private Wordpress $wordpress,
        private MainConfig $config,
        private Database $database,
        private ObjectHandler $objectHandler
    ) {
    }

    private function calculateIp(string $ip): bool|string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return base_convert((string) ip2long($ip), 10, 2);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return false;
        }

        $packedIp = inet_pton($ip);
        $bits = 15; // 16 x 8 bit = 128bit (ipv6)
        $binaryIp = '';

        while ($bits >= 0) {
            $binaryIp = sprintf('%08b', (ord($packedIp[$bits]))) . $binaryIp;
            $bits--;
        }

        return $binaryIp;
    }

    private function getCalculatedRange(string $ipRange): array
    {
        $ipRange = explode('-', $ipRange);
        $rangeBegin = $ipRange[0];
        $rangeEnd = $ipRange[1] ?? $ipRange[0];

        return [
            $this->calculateIp($rangeBegin),
            $this->calculateIp($rangeEnd)
        ];
    }

    public function isIpInRange(string $currentIp, array $ipRanges): bool
    {
        $currentIp = $this->calculateIp($currentIp);

        if ($currentIp !== false) {
            foreach ($ipRanges as $ipRange) {
                $calculatedIpRange = $this->getCalculatedRange($ipRange);

                if ($calculatedIpRange[0] !== false && $calculatedIpRange[1] !== false
                    && $calculatedIpRange[0] <= $currentIp && $currentIp <= $calculatedIpRange[1]
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getUserRole(WP_User|bool $user): array
    {
        if ($user instanceof WP_User && isset($user->{$this->database->getPrefix() . 'capabilities'}) === true) {
            $capabilities = (array) $user->{$this->database->getPrefix() . 'capabilities'};
        } else {
            $capabilities = [];
        }

        return (count($capabilities) > 0) ? array_keys($capabilities) : [AbstractUserGroup::NONE_ROLE];
    }

    public function checkUserAccess(bool|string $allowedCapability = false): bool
    {
        $currentUser = $this->wordpress->getCurrentUser();

        if ($this->wordpress->isSuperAdmin($currentUser->ID) === true
            || $allowedCapability !== false && $currentUser->has_cap($allowedCapability) === true
        ) {
            return true;
        }

        $roles = $this->getUserRole($currentUser);
        $rolesMap = array_flip($roles);

        $orderedRoles = [
            AbstractUserGroup::NONE_ROLE,
            'subscriber',
            'contributor',
            'author',
            'editor',
            'administrator'
        ];
        $orderedRolesMap = array_flip($orderedRoles);

        $userRoles = array_intersect_key($orderedRolesMap, $rolesMap);
        $rightsLevel = (count($userRoles) > 0) ? end($userRoles) : -1;
        $fullAccessRole = $this->config->getFullAccessRole();

        return (isset($orderedRolesMap[$fullAccessRole]) === true && $rightsLevel >= $orderedRolesMap[$fullAccessRole]
            || isset($rolesMap['administrator']) === true
        );
    }

    public function userIsAdmin(int|string $userId): bool
    {
        $user = $this->objectHandler->getUser($userId);
        $roles = $this->getUserRole($user);
        $rolesMap = array_flip($roles);

        return (isset($rolesMap['administrator']) === true || $this->wordpress->isSuperAdmin($userId) === true);
    }
}
