<?php

declare(strict_types=1);

namespace UserAccessManager\User;

use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\AbstractUserGroup;
use UserAccessManager\Wrapper\Wordpress;
use WP_User;

class UserHandler
{
    public const MANAGE_USER_GROUPS_CAPABILITY = 'manage_user_groups';

    private const ROLES_BY_ASCENDING_RIGHTS = [
        AbstractUserGroup::NONE_ROLE,
        'subscriber',
        'contributor',
        'author',
        'editor',
        'administrator'
    ];

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
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return false;
        }

        $binaryIp = '';

        foreach (str_split(inet_pton($ip)) as $byte) {
            $binaryIp .= sprintf('%08b', ord($byte));
        }

        return $binaryIp;
    }

    private function getCalculatedRange(string $ipRange): array
    {
        $rangeBounds = explode('-', $ipRange);

        return [
            $this->calculateIp($rangeBounds[0]),
            $this->calculateIp($rangeBounds[1] ?? $rangeBounds[0])
        ];
    }

    public function isIpInRange(string $currentIp, array $ipRanges): bool
    {
        $calculatedCurrentIp = $this->calculateIp($currentIp);

        if ($calculatedCurrentIp === false) {
            return false;
        }

        foreach ($ipRanges as $ipRange) {
            [$rangeBegin, $rangeEnd] = $this->getCalculatedRange($ipRange);

            if ($rangeBegin !== false && $rangeEnd !== false
                && $rangeBegin <= $calculatedCurrentIp && $calculatedCurrentIp <= $rangeEnd
            ) {
                return true;
            }
        }

        return false;
    }

    public function getUserRole(WP_User|bool $user): array
    {
        $capabilitiesProperty = $this->database->getPrefix() . 'capabilities';
        $capabilities = ($user instanceof WP_User && isset($user->{$capabilitiesProperty}) === true)
            ? (array) $user->{$capabilitiesProperty}
            : [];

        return ($capabilities !== []) ? array_keys($capabilities) : [AbstractUserGroup::NONE_ROLE];
    }

    private function getRolesMap(WP_User|bool $user): array
    {
        return array_flip($this->getUserRole($user));
    }

    public function checkUserAccess(bool|string $allowedCapability = false): bool
    {
        $currentUser = $this->wordpress->getCurrentUser();

        if ($this->wordpress->isSuperAdmin($currentUser->ID) === true
            || $allowedCapability !== false && $currentUser->has_cap($allowedCapability) === true
        ) {
            return true;
        }

        $rolesMap = $this->getRolesMap($currentUser);
        $rightsLevelByRole = array_flip(self::ROLES_BY_ASCENDING_RIGHTS);
        $userRightsLevels = array_intersect_key($rightsLevelByRole, $rolesMap);
        $rightsLevel = ($userRightsLevels !== []) ? end($userRightsLevels) : -1;
        $fullAccessRole = $this->config->getFullAccessRole();

        return isset($rightsLevelByRole[$fullAccessRole]) === true
            && $rightsLevel >= $rightsLevelByRole[$fullAccessRole]
            || isset($rolesMap['administrator']) === true;
    }

    public function userIsAdmin(int|string|null $userId): bool
    {
        $rolesMap = $this->getRolesMap($this->objectHandler->getUser($userId));

        return isset($rolesMap['administrator']) === true || $this->wordpress->isSuperAdmin($userId) === true;
    }
}
