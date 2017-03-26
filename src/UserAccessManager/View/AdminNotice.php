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
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

/**
 * @var UserAccessManager\Controller\AdminController $oController
 */
?>
<div id="message" class="error"><p><strong><?php echo $oController->getNotice() ?></strong></p></div>
