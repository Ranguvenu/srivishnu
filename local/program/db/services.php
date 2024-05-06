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
 * Web service for mod assign
 * @package    local_curriculum
 * @subpackage db
 * @since      Moodle 3.6
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
$functions = array(
    'local_program_submit_instance' => array(
        'classname' => 'local_program_external',
        'methodname' => 'curriculum_instance',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_deletecurriculum' => array(
        'classname' => 'local_program_external',
        'methodname' => 'delete_curriculum_instance',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_form_course_selector' => array(
        'classname' => 'local_program_external',
        'methodname' => 'program_course_selector',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'read',
    ),
    'local_program_deletesession' => array(
        'classname' => 'local_program_external',
        'methodname' => 'delete_session_instance',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_deleteclassroom' => array(
        'classname' => 'local_program_external',
        'methodname' => 'delete_offlineclassroom_instance',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_form_option_selector' => array(
        'classname' => 'local_program_external',
        'methodname' => 'program_form_option_selector',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'read',
    ),
    'local_program_session_submit_instance' => array(
        'classname' => 'local_program_external',
        'methodname' => 'program_session_instance',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_addcourse_submit_instance' => array(
        'classname' => 'local_program_external',
        'methodname' => 'program_course_instance',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_deleteprogramcourse' => array(
        'classname' => 'local_program_external',
        'methodname' => 'delete_programcourse_instance',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write',
    ),
    // 'local_curriculum_createcategory' => array(
    //     'classname' => 'local_program_external',
    //     'methodname' => 'createcategory_instance',
    //     'classpath' => 'local/program/externallib.php',
    //     // 'description' => 'All class room forms event handling',
    //     'ajax' => true,
    //     'type' => 'write',
    // ),
    'local_program_completion_settings_submit_instance' => array(
        'classname' => 'local_program_external',
        'methodname' => 'program_completion_settings_instance',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_addsemester_submit_instance' => array(
        'classname' => 'local_program_external',
        'methodname' => 'managecurriculumsemesters',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_unassign_course' => array(
        'classname' => 'local_program_external',
        'methodname' => 'curriculum_unassign_course',
        'classpath' => 'local/program/externallib.php',
        'description' => 'unasssign courses from curriculum semester',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_session_enrolments' => array(
        'classname' => 'local_program_external',
        'methodname' => 'bc_session_enrolments',
        'classpath' => 'local/program/externallib.php',
        'description' => 'Session enrolments',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_deletesemester' => array(
        'classname' => 'local_program_external',
        'methodname' => 'delete_semester_instance',
        'classpath' => 'local/program/externallib.php',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_program_submit_instance' => array(
        'classname' => 'local_program_external',
        'methodname' => 'managecurriculumprograms',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_deleteprogram' => array(
        'classname' => 'local_program_external',
        'methodname' => 'delete_program_instance',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
     'local_program_suspend_program' => array(
        'classname' => 'local_program_external',
        'methodname' => 'suspend_program_instance',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_managecurriculumStatus' => array(
        'classname' => 'local_program_external',
        'methodname' => 'managecurriculumStatus_instance',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_addyear_submit_instance' => array(
        'classname' => 'local_program_external',
        'methodname' => 'managecurriculumyears',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_addfaculty_submit_instance' => array(
        'classname' => 'local_program_external',
        'methodname' => 'addfaculty_submit_instance',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_addstudent_submit_instance' => array(
        'classname' => 'local_program_external',
        'methodname' => 'addstudent_submit_instance',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_deletesemesteryear' => array(
        'classname' => 'local_program_external',
        'methodname' => 'delete_semesteryear_instance',
        'classpath' => 'local/program/externallib.php',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_setyearcost_form_submit_instance' => array(
        'classname' => 'local_program_external',
        'methodname' => 'yearcost_submit_instance',
        'classpath' => 'local/program/externallib.php',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_unassignfaculty' => array(
        'classname' => 'local_program_external',
        'methodname' => 'unassignfaculty',
        'classpath' => 'local/program/externallib.php',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),  
    'local_program_get_programs' => array(
        'classname' => 'local_program_external',
        'methodname' => 'get_programs',
        'classpath' => 'local/program/externallib.php',
        'description' => 'Returns programs under a university and faculty',
        'ajax' => true,
        'type' => 'read'
    ),
     'local_program_get_programinstances' => array(
        'classname' => 'local_program_external',
        'methodname' => 'get_programinstances',
        'classpath' => 'local/program/externallib.php',
        'description' => 'Returns program instances created for the colleges/study centers under a university',
        'ajax' => true,
        'type' => 'read'
    ),
     'local_program_get_programinstanceyears' => array(
        'classname' => 'local_program_external',
        'methodname' => 'get_programinstanceyears',
        'classpath' => 'local/program/externallib.php',
        'description' => 'Returns year details under a programinstance created for the colleges/study centers under a university',
        'ajax' => true,
        'type' => 'read'
    ),
    'local_program_enrol_program' => array(
        'classname' => 'local_program_external',
        'methodname' => 'enrol_program',
        'classpath' => 'local/program/externallib.php',
        'description' => 'Enrolls students to year-1 semester courses of their enrolled program',
        'ajax' => true,
        'type' => 'read'
    ),
    'local_program_addclassroom_submit_instance' => array(
        'classname' => 'local_program_external',
        'methodname' => 'addclassroom_submit_instance',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_classroom_completion_settings_submit_instance' => array(
        'classname' => 'local_program_external',
        'methodname' => 'program_classroom_completion_settings_instance',
        'classpath' => 'local/program/externallib.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
    'local_program_userprograms' => array(
        'classname' => 'local_program_external',
        'methodname' => 'userprograms',
        'classpath' => 'local/program/externallib.php',
        'description' => 'userprograms',
        'ajax' => true,
        'type' => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'local_program_unassignuser' => array(
        'classname' => 'local_program_external',
        'methodname' => 'unassignuser',
        'classpath' => 'local/program/externallib.php',
        'description' => 'unassign the user from year',
        'ajax' => true,
        'type' => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'local_program_programcontent' => array(
        'classname' => 'local_program_external',
        'methodname' => 'programcontent',
        'classpath' => 'local/program/externallib.php',
        'description' => 'program content',
        'ajax' => true,
        'type' => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'local_program_classroomcontent' => array(
        'classname' => 'local_program_external',
        'methodname' => 'classroomcontent',
        'classpath' => 'local/program/externallib.php',
        'description' => 'classroom content',
        'ajax' => true,
        'type' => 'read',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    )
);

$services = array(
        'getprograms' => array(
                'functions' => array ('local_program_get_programs'),
                'restrictedusers' => 0,
                'enabled'=>1,
        ),
        'getprograminstances' => array(
                'functions' => array ('local_program_get_programinstances'),
                'restrictedusers' => 0,
                'enabled'=>1,
        ),
        'getprograminstancesyears' => array(
                'functions' => array ('local_program_get_programinstanceyears'),
                'restrictedusers' => 0,
                'enabled'=>1,
        ),
		'enrol_program' => array(
                'functions' => array ('local_program_enrol_program'),
                'restrictedusers' => 0,
                'enabled'=>1,
        )
);
