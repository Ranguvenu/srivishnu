<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Web service local plugin template external functions and service definitions.
 *
 * @package    local evalaution
 * @copyright  sreenivas 2017
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.

defined('MOODLE_INTERNAL') || die;
$functions = array(
        'local_users_submit_create_user_form' => array(
                'classname'   => 'local_users_external',
                'methodname'  => 'submit_create_user_form',
                'classpath'   => 'local/users/classes/external.php',
                'description' => 'Submit form',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_users_submit_profile_info_form' => array(
                'classname'   => 'local_users_external',
                'methodname'  => 'submit_profile_info_form',
                'classpath'   => 'local/users/classes/external.php',
                'description' => 'Submit form',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_users_delete_user' => array(
        	'classname'   => 'local_users_external',
                'methodname'  => 'delete_user',
                'classpath'   => 'local/users/classes/external.php',
                'description' => 'deleting of user',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_users_suspend_user' => array(
                'classname'   => 'local_users_external',
                'methodname'  => 'suspend_local_user',
                'classpath'   => 'local/users/classes/external.php',
                'description' => 'suspending of user',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_users_get_coladmins' => array(
                'classname'   => 'local_users_external',
                'methodname'  => 'get_coladmins',
                'classpath'   => 'local/users/classes/external.php',
                'description' => 'Returns college admin users',
                'type'        => 'read',
                'ajax' => true,
        ),
    'local_users_get_univadmins' => array(
                'classname'   => 'local_users_external',
                'methodname'  => 'get_univadmins',
                'classpath'   => 'local/users/classes/external.php',
                'description' => 'Returns University admin users',
                'type'        => 'read',
                'ajax' => true,
        ),
	/*'local_users_getactiveusers' => array(
                'classname'   => 'local_users_external',
                'methodname'  => 'getactiveusers_list',
                'classpath'   => 'local/users/classes/external.php',
                'description' => 'fetching active users',
                'type'        => 'read',
                'ajax' => true,
        ),*/
    'local_users_profile_moduledata' => array(
                'classname'   => 'local_users_external',
                'methodname'  => 'profilemoduledata',
                'classpath'   => 'local/users/classes/external.php',
                'description' => 'display module data in profile',
                'type'        => 'write',
                'ajax' => true,
                'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        ),
    'local_users_dashboard_stats' => array(
        'classname'   => 'local_users_external',
        'methodname'  => 'dashboard_stats',
        'classpath'   => 'local/users/classes/external.php',
        'description' => 'Dashboard stats for mobile',
        'type'        => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'local_users_pending_activities' => array(
        'classname' => 'local_users_external',
        'methodname' => 'pending_activities',
        'description' => 'Get pending_activities',
        'classpath' => 'local/users/classes/external.php',
        'type' => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
);

$services = array(
        'get_coladmins' => array(
                'functions' => array ('local_users_get_coladmins'),
                'restrictedusers' => 0,
                'enabled'=>1,
        ),
    'get_univadmins' => array(
                'functions' => array ('local_users_get_univadmins'),
                'restrictedusers' => 0,
                'enabled'=>1,
        )
);
