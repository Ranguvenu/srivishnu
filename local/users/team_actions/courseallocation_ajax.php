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
 * Course Allocation AJAX Response
 *
 * @package    block_courseallocation
 * @copyright  2017 Arun Kumar Mukka
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

global $CFG, $USER, $PAGE;
use local_users\output\courseallocation;
use local_users\output\courseallocation_lib;
use context_system;

$systemcontext = context_system::instance();
require_login();
$PAGE->set_context($systemcontext);

$courseallocation = new courseallocation;
$courseallocation_lib = new courseallocation_lib();

$action = required_param('action', PARAM_TEXT);
$user = optional_param('user', 0, PARAM_INT);
// $type = optional_param('type', 'users', PARAM_RAW);
$learningtype = optional_param('learningtype', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_RAW);
$allocatecourse = optional_param('allocatecourse', '', PARAM_RAW);

switch($action) {
    case 'departmentusers':
        $return = $courseallocation_lib->get_team_users($search = false);
    break;
    case 'departmentmodules':
        if($learningtype == 1){
            $return = $courseallocation->get_team_courses_view($user, $search = false);
        }elseif($learningtype == 2){
            $return = $courseallocation->get_team_classrooms_view($user, $search = false);
        }elseif($learningtype == 3){
            $return = $courseallocation->get_team_programs_view($user, $search = false);
        }elseif($learningtype == 4){
            // $return = $courseallocation->get_team_learningpaths_view($user, $search = false);
            $return = '';
        }else{
            $return = $courseallocation->get_team_courses_view($user, $search = false);
        }
    break;
    case 'searchdata':
        if($learningtype == 'users'){
            $return = $courseallocation_lib->get_team_users($search);
        }else{
            // searchtype
            if($learningtype == 1){
                $return = $courseallocation->get_team_courses_view($user, $search);
            }elseif($learningtype == 2){
                $return = $courseallocation->get_team_classrooms_view($user, $search);
            }elseif($learningtype == 3){
                $return = $courseallocation->get_team_programs_view($user, $search);
            }elseif($learningtype == 4){
                // $return = $courseallocation->get_team_learningpaths_view($user, $search = false);
                $return = '';
            }else{
                $return = $courseallocation->get_team_courses_view($user, $search);
            }
            // $return = $courseallocation->get_team_courses_view($user, $search);
        }
    break;
    case 'courseallocate':
        $return = $courseallocation_lib->courseallocation($learningtype, $user, $allocatecourse);
    break;
}
echo json_encode($return);