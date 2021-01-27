<?php
/**
 * DynamicGroupsController.php
 *
 * The DynamicGroupsController class file.
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

use UserAccessManager\UserGroup\DynamicUserGroup;
use WP_Role;

/**
 * Class DynamicGroupsController
 *
 * @package UserAccessManager\Controller\Backend
 */
class DynamicGroupsController extends ObjectController
{
    /**
     * Returns the dynamic user groups for the ajax request.
     */
    public function getDynamicGroupsForAjax()
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
                    'name' => TXT_UAM_USER . ": {$element->display_name} ($element->user_login)",
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
            if (strpos(strtolower($role['name']), strtolower($search)) !== false) {
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
