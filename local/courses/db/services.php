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
 * @package    local courses
 * @copyright  Shivani 2017
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.

defined('MOODLE_INTERNAL') || die;
$functions = array(
        'local_courses_submit_create_course_form' => array(
                'classname'   => 'local_courses_external',
                'methodname'  => 'submit_create_course_form',
                'classpath'   => 'local/courses/classes/external.php',
                'description' => 'Submit form',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_courses_submit_create_category_form' => array(
                'classname'   => 'local_courses_external',
                'methodname'  => 'submit_create_category_form',
                'classpath'   => 'local/courses/classes/external.php',
                'description' => 'Submit form',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_courses_submit_delete_category_form' => array(
                'classname'   => 'local_courses_external',
                'methodname'  => 'submit_delete_category_form',
                'classpath'   => 'local/courses/classes/external.php',
                'description' => 'Submit form',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_courses_departmentlist' => array(
                'classname'   => 'local_courses_external',
                'methodname'  => 'departmentlist',
                'classpath'   => 'local/courses/classes/external.php',
                'description' => 'Department List',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_courses_suspend_course' => array(
                'classname'   => 'local_courses_external',
                'methodname'  => 'suspend_local_course',
                'classpath'   => 'local/courses/classes/external.php',
                'description' => 'suspending of course',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_courses_availability_course' => array(
                'classname'   => 'local_courses_external',
                'methodname'  => 'availability_local_course',
                'classpath'   => 'local/courses/classes/external.php',
                'description' => 'availiability of course',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_courses_deletecourse' => array(
                'classname' => 'local_courses_external',
                'methodname' => 'delete_course',
                'classpath'   => 'local/courses/classes/external.php',
                'description' => 'deletion of courses',
                'ajax' => true,
                'type' => 'write'
        ),
        'local_courses_form_option_selector' => array(
                'classname' => 'local_courses_external',
                'methodname' => 'global_filters_form_option_selector',
                'classpath' => 'local/courses/classes/external.php',
                'description' => 'All global filters forms event handling',
                'ajax' => true,
                'type' => 'read',
        ),
        'local_courses_create_courses_from_api' => array(
        'classname' => 'local_create_courses_from_api_external',
        'methodname' => 'create_courses_from_api',
        'classpath' => 'local/courses/externallib.php',
        'description' => 'Create Courses From API',
        'ajax' => true,
        'type' => 'read'
    ),
    'local_courses_get_users_course_status_information' => array(
        'classname' => 'local_courses_external',
        'methodname' => 'get_users_course_status_information',
        'classpath' => 'local/courses/classes/external.php',
        'description' => 'get completed courses list',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'local_courses_get_recently_enrolled_courses' => array(
        'classname' => 'local_courses_external',
        'methodname' => 'get_recently_enrolled_courses',
        'classpath' => 'local/courses/classes/external.php',
        'description' => 'get recently enrolled courses list',
        'type' => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
       'local_courses_get_courseinstances' => array(
        'classname' => 'local_create_courses_from_api_external',
        'methodname' => 'get_courseinstances',
        'classpath' => 'local/courses/externallib.php',
        'description' => 'Returns course instances created for the colleges/study centers under a university',
        'ajax' => true,
        'type' => 'read'
    ),
    'local_courses_courseenrol_users' => array(
        'classname' => 'local_create_courses_from_api_external',
        'methodname' => 'courseenrol_users',
        'classpath' => 'local/courses/externallib.php',
        'description' => 'user enrolments for course',
        'ajax' => true,
        'type' => 'read'
    ),
    'local_courses_courses_view' => array(
        'classname' => 'local_courses_external',
        'methodname' => 'courses_view',
        'classpath' => 'local/courses/classes/external.php',
        'description' => 'List all courses in card view',
        'ajax' => true,
        'type' => 'read',
    ),
);

$services = array(
        'create_courses_from_api' => array(
        'functions' => array ('local_courses_create_courses_from_api'),
        'restrictedusers' => 0,
        'enabled'=>1,
        ),
        'get_courseinstances' => array(
                'functions' => array ('local_courses_get_courseinstances'),
                'restrictedusers' => 0,
                'enabled'=>1,
        ), 
        'courseenrol_users' => array(
                'functions' => array ('local_courses_courseenrol_users'),
                'restrictedusers' => 0,
                'enabled'=>1,
        ),
);
