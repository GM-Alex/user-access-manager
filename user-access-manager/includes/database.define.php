<?php
/**
 * database.define.php
 * 
 * Defines needed for the database.
 * 
 * PHP versions 5
 * 
 * @category  UserAccessManager
 * @package   UserAccessManager
 * @author    Alexander Schneider <alexanderschneider85@googlemail.com>
 * @copyright 2008-2013 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

global $wpdb;

define('DB_ACCESSGROUP', $wpdb->prefix . 'uam_accessgroups');
define('DB_ACCESSGROUP_TO_OBJECT', $wpdb->prefix . 'uam_accessgroup_to_object');