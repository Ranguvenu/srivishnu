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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
$functions = array(
    'local_curriculum_submit_data' => array(
        'classname' => 'local_curriculum_external',
        'methodname' => 'submit_curriculum_data',
        'classpath' => 'local/curriculum/external.php',
        'description' => 'Inserting Curriculum Data',
        'ajax' => true,
        'type' => 'write'
    ),
     'local_curriculum_deletecurriculum' => array(
        'classname' => 'local_curriculum_external',
        'methodname' => 'delete_curriculum_instance',
        'classpath' => 'local/curriculum/external.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
        'local_curriculum_addyear_submit_data' => array(
        'classname' => 'local_curriculum_external',
        'methodname' => 'managecurriculumyears',
        'classpath' => 'local/curriculum/external.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
        'local_curriculum_addsemester_submit_data' => array(
        'classname' => 'local_curriculum_external',
        'methodname' => 'managecurriculumsemesters',
        'classpath' => 'local/curriculum/external.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
        'local_curriculum_addcourse_submit_data' => array(
        'classname' => 'local_curriculum_external',
        'methodname' => 'curriculum_course_instance',
        'classpath' => 'local/curriculum/external.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
        'local_curriculum_unassign_course' => array(
        'classname' => 'local_curriculum_external',
        'methodname' => 'curriculum_unassign_course',
        'classpath' => 'local/curriculum/external.php',
        'description' => 'unasssign courses from curriculum semester',
        'ajax' => true,
        'type' => 'write'
    ),
        'local_curriculum_addfaculty_submit_data' => array(
        'classname' => 'local_curriculum_external',
        'methodname' => 'addfaculty_submit_data',
        'classpath' => 'local/curriculum/external.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
        'local_curriculum_addstudent_submit_data' => array(
        'classname' => 'local_curriculum_external',
        'methodname' => 'addstudent_submit_data',
        'classpath' => 'local/curriculum/external.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
     'local_curriculum_unassignfaculty' => array(
        'classname' => 'local_curriculum_external',
        'methodname' => 'unassignfaculty',
        'classpath' => 'local/curriculum/external.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),  
       'local_curriculum_deletesemester' => array(
        'classname' => 'local_curriculum_external',
        'methodname' => 'delete_semester_data',
        'classpath' => 'local/curriculum/external.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
       'local_curriculum_deletesemesteryear' => array(
        'classname' => 'local_curriculum_external',
        'methodname' => 'delete_semesteryear_data',
        'classpath' => 'local/curriculum/external.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'write'
    ),
       'local_curriculum_form_course_selector' => array(
        'classname' => 'local_curriculum_external',
        'methodname' => 'program_course_selector',
        'classpath' => 'local/curriculum/external.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'read',
    ),
        'local_curriculum_form_option_selector' => array(
        'classname' => 'local_curriculum_external',
        'methodname' => 'program_form_option_selector',
        'classpath' => 'local/curriculum/external.php',
        'description' => 'All class room forms event handling',
        'ajax' => true,
        'type' => 'read',
    ),
);

