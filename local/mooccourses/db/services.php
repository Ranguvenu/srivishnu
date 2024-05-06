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
        'local_mooccourses_submit_create_mooccourse_form' => array(
                'classname'   => 'local_mooccourses_external',
                'methodname'  => 'submit_create_mooccourse_form',
                'classpath'   => 'local/mooccourses/classes/external.php',
                'description' => 'Submit form',
                'type'        => 'write',
                'ajax' => true,
        ),
          'local_mooccourses_submit_create_course_form' => array(
                'classname'   => 'local_mooccourses_external',
                'methodname'  => 'submit_course_create_form',
                'classpath'   => 'local/mooccourses/classes/external.php',
                'description' => 'Submit form',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_mooccourses_submit_edit_mooccourse_form' => array(
                'classname'   => 'local_mooccourses_external',
                'methodname'  => 'submit_mooccourse_edit_form',
                'classpath'   => 'local/mooccourses/classes/external.php',
                'description' => 'Submit form',
                'type'        => 'write',
                'ajax' => true,
        ),
         'local_mooccourse_delete_mooccourse' => array(
                'classname'   => 'local_mooccourses_external',
                'methodname'  => 'delete_mooccourse',
                'classpath'   => 'local/mooccourses/classes/external.php',
                'description' => 'Submit form',
                'type'        => 'write',
                'ajax' => true,
        ),
        'local_mooccourses_submit_enrolusers_form' => array(
        'classname'   => 'local_mooccourses_external',
        'methodname'  => 'addstudent_submit_instance',
        'classpath'   => 'local/mooccourses/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax'        => true,
        'type'        => 'write'
    ),
             
);

/*$services = array(
        'create_courses_from_api' => array(
        'functions' => array ('local_courses_create_courses_from_api'),
        'restrictedusers' => 0,
        'enabled'=>1,   
        )
);*/
