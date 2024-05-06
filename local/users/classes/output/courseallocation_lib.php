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
 * Course Allocation block
 *
 * @package    block_courseallocation
 * @copyright  2017 Arun Kumar Mukka
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_users\output;
require_once($CFG->dirroot . '/local/classroom/lib.php');
use local_classroom\classroom;
use local_program\program;
// use local_learningplan\lib\lib as learningplanclass;

defined('MOODLE_INTERNAL') || die;
class courseallocation_lib{

    public function get_team_users($search = false){
        global $DB, $USER, $OUTPUT;
        if($search){
            $condition = " AND CONCAT(u.firstname, ' ', u.lastname) LIKE '%$search%' ";
        } else {
            $condition = "";
        }
        $departmentuserssql = "SELECT u.* FROM {user} as u
                                    WHERE u.open_supervisorid = $USER->id AND u.id != $USER->id
                                    AND u.confirmed = 1 AND u.suspended = 0 AND u.deleted = 0 AND u.id > 2".$condition;
        
        $departmentusers = $DB->get_records_sql($departmentuserssql);

        if(!empty($departmentusers)){
        	$return = '';
        	foreach ($departmentusers as $departmentuser) {
	        	$return .= "<li class='li-course'>
	                            <div class='task-checkbox mt-10px'>
	                                <div class='checker'>
	                                	<span class='checked'>
	                                		<input type = 'radio' id='userid' name = 'allocateuser' class='liChild allocateuser' 
	                                			value='".$departmentuser->id."' onclick='(function(e){require(\"local_users/courseallocation\").select_type({user: ".$departmentuser->id.", learningtype: 1, pluginname: \"".get_string("pluginname", "local_courses")."\"})})(event)'>
	                                	</span>
	                                </div>
	                            </div>
	                            <div class='task-title'>
	                                ".$OUTPUT->user_picture($departmentuser, array('class'=>'img-circle img-member user_picture', 'link' => false))."
	                                <span class='task-title-sp'>" . fullname($departmentuser) . "</span>
	                            </div>
	                        </li>";
            }
        }else{
        	$return = "<li class='li-course empty_data'>
                    	<div class='alert alert-info text-center'>".get_string('nousersfound')."</div>
                    </li>";
        }
        return $return;
    }

	public function get_team_courses($user, $search = false){
		global $DB, $USER,$CFG;

		if(empty($user)){
			return get_string('invaliduser');
		}
		if($search){
			$condition = " AND c.fullname LIKE '%$search%' ";
		}else{
			$condition = " ";
		}
		$costcenterid = $DB->get_field('user', 'open_costcenterid', array('id' => $user));
		if($costcenterid > 0){
			$condition .= " AND c.open_costcenterid = ".$costcenterid;
		}
		$courses_sql = "SELECT c.id, c.fullname 
										FROM {course} as c
										WHERE c.id > 1 AND FIND_IN_SET(3, c.open_identifiedas) AND c.visible = 1".$condition;
		$courses = $DB->get_records_sql_menu($courses_sql);

		return $courses;
	}

	public function get_team_classrooms($user, $search = false){
		global $DB, $USER,$CFG;

		if(empty($user)){
			return get_string('invaliduser');
		}
		if($search){
			$condition = " AND c.name LIKE '%$search%' ";
		}else{
			$condition = " ";
		}
		$costcenterid = $DB->get_field('user', 'open_costcenterid', array('id' => $user));
		if($costcenterid > 0){
			$condition .= " AND c.costcenter = ".$costcenterid;
		}
		$classrooms_sql = "SELECT c.id, c.name 
							FROM {local_classroom} as c
							JOIN {local_classroom_users} as cu
							WHERE c.visible = 1 AND c.status = 1".$condition;
		$classrooms = $DB->get_records_sql_menu($classrooms_sql);

		return $classrooms;
	}
	public function get_team_programs($user, $search = false){
		global $DB, $USER;
			if($search){
				$condition = " AND p.name LIKE '%$search%' ";
			} else {
				$condition = "";
			}
			$costcenterid = $DB->get_field('user', 'open_costcenterid', array('id' => $user));
			if($costcenterid > 0){
				$condition .= " AND p.costcenter = ".$costcenterid;
			}
			$programssql = "SELECT p.id, p.name FROM {local_program} as p
										WHERE p.visible = 1".$condition;
			
			$programs = $DB->get_records_sql_menu($programssql);
			return $programs;
	}

	// public function get_team_learningpaths($user, $search = false){
	// 	global $DB, $USER;
	// 		if($search){
	// 			$condition = " AND l.name LIKE '%$search%' ";
	// 		} else {
	// 			$condition = "";
	// 		}
	// 		$costcenterid = $DB->get_field('user', 'open_costcenterid', array('id' => $user));
	// 		if($costcenterid > 0){
	// 			$condition .= " AND l.costcenter = ".$costcenterid;
	// 		}
	// 		$learningpathssql = "SELECT l.id, l.name FROM {local_learningplan} as l
	// 									WHERE l.visible = 1".$condition;
			
	// 		$learningpaths = $DB->get_records_sql_menu($learningpathssql);
	// 		return $learningpaths;
	// }
	
	public function courseallocation($learningtype, $allocateusers, $allocatecourses){
		global $DB, $USER, $CFG;

		$allocatecourses = explode(',', $allocatecourses);
		
		switch ($learningtype) {
			case '1'://courses
				$return = array();
				if(!empty($allocatecourses)){
					require_once($CFG->dirroot . '/lib/enrollib.php');
					$manual = enrol_get_plugin('manual');
					$studentroleid = $DB->get_field('role', 'id', array('archetype' => 'student'));
					foreach($allocatecourses as $allocatecourse){
						$sql = "SELECT FIND_IN_SET(3,open_identifiedas) FROM {course} WHERE id = $allocatecourse";
						$iselearning = $DB->get_record_sql($sql);
						$instance = $DB->get_record('enrol',
									 array('courseid' => $allocatecourse, 'enrol' => 'manual', 'roleid' => $studentroleid));

						if($instance){
							$user_enrollment_exists = $DB->record_exists('user_enrolments', array('enrolid' => $instance->id, 'userid' => $allocateusers));
							if(!$user_enrollment_exists){
									$out = $manual->enrol_user($instance, $allocateusers, $studentroleid);
									$return[$allocatecourse] = true;
							}else{
								$return[$allocatecourse] = false;
							}
						}else{
							$return[$allocatecourse] = false;
						}
					}
					return $return;
				}else{
					return false;
				}
				break;
			case '2'://classrooms
				$return = array();
				if(!empty($allocatecourses)){
					$classroomclass = new classroom();
					foreach($allocatecourses as $allocatecourse){
						$instance = $DB->record_exists('local_classroom_users', array('classroomid' => $allocatecourse, 'userid' => $allocateusers));
						if(!$instance){
							$out = $classroomclass->classroom_self_enrolment($allocatecourse, $allocateusers);
							$return[$allocatecourse] = true;
						}else{
							$return[$allocatecourse] = false;
						}
					}
					return $return;
				}else{
					return false;
				}
				break;
			case '3'://programs
				$return = array();
				if(!empty($allocatecourses)){
					$programclass = new program();
					foreach($allocatecourses as $allocatecourse){
						$instance = $DB->record_exists('local_program_users', array('programid' => $allocatecourse, 'userid' => $allocateusers));
						if(!$instance){
							$out = $programclass->program_self_enrolment($allocatecourse, $allocateusers);
							$return[$allocatecourse] = true;
						}else{
							$return[$allocatecourse] = false;
						}
					}
					return $return;
				}else{
					return false;
				}
				break;
			case '4'://learningplans
				// $return = array();
				// if(!empty($allocatecourses)){
				// 	$learningplanclass = new local_learningplan\lib();
				// 	foreach($allocatecourses as $allocatecourse){
				// 		$instance = $DB->record_exists('local_learningplan_user', array('planid' => $allocatecourse, 'userid' => $allocateusers));
				// 		if(!$instance){
				// 			// $allocatecourse, $allocateusers
				// 			$record = new \stdClass();
				// 			$record->planid = $allocatecourse;
				// 			$record->userid = $allocateusers;
				// 			$record->timecreated = time();
				// 			$record->usercreated = $allocateusers;
				// 			$record->timemodified = time();
				// 			$record->usermodified = $allocateusers;
				// 			$out = $learningplanclass->assign_users_to_learningplan($record);
				// 			$return = true;
				// 		}else{
				// 			$return = false;
				// 		}
				// 	}
				// 	if($return){
				// 		return true;
				// 	}else{
				// 		return false;
				// 	}
				// }else{
					return false;
				// }
				break;
		}
		return false;
	}
}