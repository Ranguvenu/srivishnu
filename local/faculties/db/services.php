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
 * @package    local school
 * @copyright  srilakshmi 2017
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.

defined('MOODLE_INTERNAL') || die;
$functions = array(
        'local_faculties_submit_facultyform_data' => array(
                'classname'   => 'local_faculties_external',
                'methodname'  => 'submit_facultyform_data',
                'classpath'   => 'local/faculties/classes/external.php',
                'description' => 'Submit form',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_faculties_delete_faculty' => array(
            'classname'   => 'local_faculties_external',
                'methodname'  => 'delete_faculty',
                'description' => 'deleting faculty',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_faculties_suspend_faculty' => array(
                'classname'   => 'local_faculties_external',
                'methodname'  => 'faculty_status_confirm',
                'description' => 'changing status of faculty',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_faculties_form_option_selector' => array(
                'classname'   => 'local_faculties_external',
                'methodname' => 'global_filters_form_option_selector',
                'classpath' => 'local/faculties/classes/external.php',
                'description' => 'All global filters forms event handling',
                'ajax' => true,
                'type' => 'read',
        ),
      'local_faculties_get_faculties' => array(
                'classname'   => 'local_faculties_external',
                'methodname'  => 'get_faculties',
                'classpath'   => 'local/faculties/classes/external.php',
                'description' => 'get faculties',
                'type'        => 'read',
                'ajax' => true,
        ),
      'local_faculties_create_faculties_from_api' => array(
        'classname' => 'local_faculties_faculties_from_api_external',
        'methodname' => 'create_faculties_from_api',
        'classpath' => 'local/faculties/externallib.php',
        'description' => 'Create Faculties From API',
        'ajax' => true,
        'type' => 'read'
    )        
);

$services = array(
        'getfaculties' => array(
                'functions' => array ('local_faculties_get_faculties'),
                'restrictedusers' => 0,
                'enabled'=>1,
        ),
        'create_faculties_from_api' => array(
        'functions' => array ('local_faculties_create_faculties_from_api'),
        'restrictedusers' => 0,
        'enabled'=>1,   
        )
);
