<?php
// This file is part of Moodle - http://moodle.org/
//
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
 * Web services
 * @package    local
 * @subpackage local_sisprograms
 * @since      Moodle 3.6
 * @copyright  2019 Pramod Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
$functions = array(
    'local_sisprograms_create_user_and_enrolto_onlinecourse' => array(
        'classname' => 'local_create_user_and_enrolto_onlinecourse_external',
        'methodname' => 'create_user_and_enrolto_onlinecourse',
        'classpath' => 'local/sisprograms/externallib.php',
        'description' => 'Upon a trigger from student module-dashboard to view online course content of a course which is under his regular/offline program, this service will create student LMS account and enrol him to online course ',
        'ajax' => true,
        'type' => 'read'
    ),
	'local_sisprograms_create_faculty_and_enrolto_onlinecourse' => array(
        'classname' => 'local_create_faculty_and_enrolto_onlinecourse_external',
        'methodname' => 'create_faculty_and_enrolto_onlinecourse',
        'classpath' => 'local/sisprograms/externallib.php',
        'description' => 'Upon a trigger from Faculty module-dashboard to view online course content of a course which is under his regular/offline program, this service will create faculty LMS account and enrol him to online course ',
        'ajax' => true,
        'type' => 'read'
    ),
    'local_sisprograms_create_programs_from_api' => array(
        'classname' => 'local_create_programs_from_api_external',
        'methodname' => 'create_programs_from_api',
        'classpath' => 'local/sisprograms/externallib.php',
        'description' => 'Create Program From API',
        'ajax' => true,
        'type' => 'read'
    ),
    'local_sisprograms_create_exams_from_api' => array(
        'classname' => 'local_create_exams_from_api_external',
        'methodname' => 'create_exams_from_api',
        'classpath' => 'local/sisprograms/externallib.php',
        'description' => 'Create Exams From API',
        'ajax' => true,
        'type' => 'read'
    ),
     'local_sisprograms_create_branch_from_api' => array(
        'classname' => 'local_create_branch_from_api_external',
        'methodname' => 'create_branch_from_api',
        'classpath' => 'local/sisprograms/externallib.php',
        'description' => 'Create Branch From API',
        'ajax' => true,
        'type' => 'read'
    )
    
);

$services = array(
        'create_user_and_enrolto_onlinecourse' => array(
                'functions' => array ('local_sisprograms_create_user_and_enrolto_onlinecourse'),
                'restrictedusers' => 0,
                'enabled'=>1,
        ),
		'create_faculty_and_enrolto_onlinecourse' => array(
                'functions' => array ('local_sisprograms_create_faculty_and_enrolto_onlinecourse'),
                'restrictedusers' => 0,
                'enabled'=>1,
        ),
        'create_programs_from_api' => array(
                'functions' => array ('local_sisprograms_create_programs_from_api'),
                'restrictedusers' => 0,
                'enabled'=>1,
        ),
         'create_exams_from_api' => array(
                'functions' => array ('local_sisprograms_create_exams_from_api'),
                'restrictedusers' => 0,
                'enabled'=>1,
        ),
         'create_branch_from_api' => array(
                'functions' => array ('local_sisprograms_create_branch_from_api'),
                'restrictedusers' => 0,
                'enabled'=>1,
        )
);