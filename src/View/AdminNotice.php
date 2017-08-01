<?php
/**
 * AdminNotice.php
 *
 * Shows a notice at the admin panel.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

/**
 * @var UserAccessManager\Controller\AdminController $controller
 */
?>
<div id="message" class="error"><p><strong><?php echo $controller->getNotice() ?></strong></p></div>
