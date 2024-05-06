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
 * List the tool provided
 *
 * @package   block
 * @subpackage  facultydashboard
 * @copyright  2017  Syed Hameed Ullah <hameed@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $DB, $OUTPUT, $USER, $CFG, $PAGE;
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
use core_component;
// require_once $CFG->dirroot . '/blocks/facultydashboard/lib.php';
require_once $CFG->dirroot . '/local/includes.php';

class block_program_courselist_renderer extends plugin_renderer_base {

	public function program_courselist_view() {
		global $DB, $PAGE, $USER, $CFG, $OUTPUT;
		
		// if(has_capability('block/facultydashboard:view', $systemcontext)){
		// 	$studentrole = true;
		// }else{
		// 	$studentrole = false;
		// }
         $enrolled = "SELECT count(p.id)
                  FROM {local_program} AS p
                  JOIN {local_curriculum_users} AS cu ON cu.programid = p.id
                 WHERE cu.userid = $USER->id  ";
        /*if($filter == 'programinprogress'){
           $sql .= " AND cu.completion_status = 0 AND cu.completiondate = 0 AND p.status = 1";
        }
        if($filter == 'programcompleted'){
            $sql .= " AND cu.completion_status = 1 AND cu.completiondate > 0";
        }
        if(!empty($filter_text)){
            $sql .= " AND p.fullname LIKE '%%$filter_text%%'";
        }*/
        $enrolled .= " ORDER BY p.id desc";
        $programs_enroled = $DB->count_records_sql($enrolled);
        $inprogressprograms = $DB->count_records_sql("SELECT count(p.id)
                  FROM {local_program} AS p
                  JOIN {local_curriculum_users} AS cu ON cu.programid = p.id
                 WHERE cu.userid = $USER->id AND cu.completion_status = 0 AND cu.completiondate = 0 AND p.status = 1");
        $completedprograms = $DB->count_records_sql("SELECT count(p.id)
                  FROM {local_program} AS p
                  JOIN {local_curriculum_users} AS cu ON cu.programid = p.id
                 WHERE cu.userid = $USER->id AND cu.completion_status = 1 AND cu.completiondate > 0");
        $enrolledcourses = $DB->count_records_sql("SELECT count(c.id) FROM {course} c JOIN {enrol} e ON e.courseid = c.id JOIN {user_enrolments} ue ON e.id = ue.enrolid WHERE c.forpurchaseindividually IS NOT NULL AND ue.userid =".$USER->id);
       
        $completedcourses = $DB->count_records_sql("SELECT count(c.id) FROM {course} c JOIN {enrol} e ON e.courseid = c.id JOIN {user_enrolments} ue ON e.id = ue.enrolid JOIN {course_completions} cc ON cc.course = c.id WHERE ue.userid = ".$USER->id." AND cc.timecompleted IS NOT NULL AND c.forpurchaseindividually IS NOT NULL");
         $inprogresscourses = $enrolledcourses-$completedcourses;


		$data = [
			'program_enroled' => $programs_enroled,
			'program_inprogress' => $inprogressprograms,
			'program_completed' => $completedprograms,
			'course_enroled' => $enrolledcourses,
			'course_inprogress' => $inprogresscourses,
			'course_completed' => $completedcourses
			
		];
		return $OUTPUT->render_from_template('block_program_courselist/programcourselist', $data);
	}

  public function faculty_program_courselist_view() {
        global $DB, $PAGE, $USER, $CFG, $OUTPUT;
        $programsql = "SELECT COUNT(DISTINCT(p.id))
                  FROM {local_program} AS p
            JOIN {local_curriculum} AS cc ON cc.program = p.id
            JOIN {local_cc_session_trainers} AS lcst ON lcst.programid = p.id 
            LEFT JOIN {local_cc_course_sessions} AS lccs ON lccs.programid = lcst.programid WHERE (lcst.trainerid = $USER->id OR lccs.trainerid = $USER->id) ";
        $programs_count = $DB->count_records_sql($programsql);

        $coursessql = "SELECT COUNT(c.id)
                      FROM {course} c JOIN {context} as cnx ON cnx.instanceid = c.id JOIN {role_assignments} as ra ON ra.contextid = cnx.id JOIN {role} as r ON r.id = ra.roleid WHERE r.shortname = 'faculty' AND cnx.contextlevel = 50 AND c.forpurchaseindividually IN (1,2) AND ra.userid = ".$USER->id;
        $courses_count = $DB->count_records_sql($coursessql);

        $sessionsql = "SELECT COUNT(bcs.id)
                      FROM {local_cc_course_sessions} AS bcs
                LEFT JOIN {user} AS u ON u.id = bcs.trainerid
                LEFT JOIN {local_location_room} AS lr ON lr.id = bcs.roomid 
                LEFT JOIN {local_cc_semester_classrooms} AS class ON class.id = bcs.bclcid WHERE 1 = 1 AND bcs.courseid = 0 AND bcs.trainerid = $USER->id";
        $sessions_count = $DB->count_records_sql($sessionsql);

        $data = [
          'programs_count' => $programs_count,
          'courses_count' => $courses_count,
          'sessions_count' => $sessions_count          
        ];
        return $OUTPUT->render_from_template('block_program_courselist/facultyprogramcourselist', $data);
  }
}
