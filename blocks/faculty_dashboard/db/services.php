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
 * Learning plan webservice functions.
 *
 *
 * @package    block_faculty_dashboard
 * @copyright  2020 Sarath Kumar Setti <sarath@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
  
    // added by sarath
    'block_faculty_dashboard_data_for_elearning_courses' => array(
        'classname'    => 'block_faculty_dashboard\external',
        'methodname'   => 'data_for_elearning_courses',
        'classpath'    => '',
        'description'  => 'Load the data for the elearning courses.',
        'type'         => 'read',
        'capabilities' => '',
        'ajax'         => true,
     
    ),
    'block_faculty_dashboard_data_for_program_courses' => array(
        'classname'    => 'block_faculty_dashboard\external',
        'methodname'   => 'data_for_program_courses',
        'classpath'    => '',
        'description'  => 'Load the data for the program courses.',
        'type'         => 'read',
        'capabilities' => '',
        'ajax'         => true,
    ),
    'block_faculty_dashboard_data_for_classroom_sessions' => array(
        'classname'    => 'block_faculty_dashboard\external',
        'methodname'   => 'data_for_classroom_sessions',
        'classpath'    => '',
        'description'  => 'Load the data for the classroom sessions.',
        'type'         => 'read',
        'capabilities' => '',
        'ajax'         => true,
    ),    
);

