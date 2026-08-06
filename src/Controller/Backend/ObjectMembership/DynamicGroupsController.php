<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend\ObjectMembership;

use JetBrains\PhpStorm\NoReturn;
use UserAccessManager\UserGroup\DynamicUserGroup;
use WP_Role;

class DynamicGroupsController extends ObjectController
{
    #[NoReturn]
    public function getDynamicGroupsForAjax(): void
    {
        if ($this->checkUserAccess() === false) {
            echo json_encode([]);
            $this->php->callExit();
            return;
        }

        $searchTerms = explode(',', (string) $this->getRequestParameter('q'));
        $search = trim(end($searchTerms));

        echo json_encode(array_merge($this->getMatchingUsers($search), $this->getMatchingRoles($search)));
        $this->php->callExit();
    }

    private function getMatchingUsers(string $search): array
    {
        $users = $this->wordpress->getUsers([
            'search' => '*' . $search . '*',
            'fields' => ['ID', 'display_name', 'user_login', 'user_email']
        ]);

        return array_map(
            function ($user) {
                return [
                    'id' => $user->ID,
                    'name' => TXT_UAM_USER . ": $user->display_name ($user->user_login)",
                    'type' => DynamicUserGroup::USER_TYPE
                ];
            },
            $users
        );
    }

    private function getMatchingRoles(string $search): array
    {
        /**
         * @var WP_Role[] $roles
         */
        $roles = $this->wordpress->getRoles()->roles;
        $matches = [];

        foreach ($roles as $key => $role) {
            if (str_contains(strtolower($role['name']), strtolower($search))) {
                $matches[] = [
                    'id' => $key,
                    'name' => TXT_UAM_ROLE . ': ' . $role['name'],
                    'type' => DynamicUserGroup::ROLE_TYPE
                ];
            }
        }

        return $matches;
    }
}
