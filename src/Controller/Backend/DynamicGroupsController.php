<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

use JetBrains\PhpStorm\NoReturn;
use UserAccessManager\UserGroup\DynamicUserGroup;
use WP_Role;

class DynamicGroupsController extends ObjectController
{
    /**
     * Returns the dynamic user groups for the ajax request.
     */
    #[NoReturn]
    public function getDynamicGroupsForAjax(): void
    {
        if ($this->checkUserAccess() === false) {
            echo json_encode([]);
            $this->php->callExit();
            return;
        }

        $search = $this->getRequestParameter('q');
        $searches = explode(',', $search);
        $search = trim(end($searches));

        $users = $this->wordpress->getUsers([
            'search' => '*' . $search . '*',
            'fields' => ['ID', 'display_name', 'user_login', 'user_email']
        ]);
        $matches = array_map(
            function ($element) {
                return [
                    'id' => $element->ID,
                    'name' => TXT_UAM_USER . ": $element->display_name ($element->user_login)",
                    'type' => DynamicUserGroup::USER_TYPE
                ];
            },
            $users
        );

        /**
         * @var WP_Role[] $roles
         */
        $roles = $this->wordpress->getRoles()->roles;

        foreach ($roles as $key => $role) {
            if (str_contains(strtolower($role['name']), strtolower($search))) {
                $matches[] = [
                    'id' => $key,
                    'name' => TXT_UAM_ROLE . ': ' . $role['name'],
                    'type' => DynamicUserGroup::ROLE_TYPE
                ];
            }
        }

        echo json_encode($matches);
        $this->php->callExit();
    }
}
