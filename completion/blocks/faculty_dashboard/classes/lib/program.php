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
 * Programs
 *
 * @package    block_faculty_dashboard
 * @copyright  2018 Sarath Kumar Setti <sarath@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_faculty_dashboard\lib;

use renderable;
use renderer_base;
use templatable;
use context_course;
use stdClass;

class program{

    public static function faculty_programs($stable,$filtervalues) {
        global $DB, $USER, $CFG, $PAGE;
        require_once $CFG->dirroot.'/local/program/lib.php';
        
        $countsql = "SELECT COUNT(DISTINCT(p.id)) ";
        $selectsql  = "SELECT DISTINCT(p.id) as id, p.fullname, cc.duration,cc.duration_format, p.year, p.shortcode, p.curriculumsemester, cc.id AS curriculumid, p.program_logo";
        $formsql   =" FROM {local_program} AS p
            JOIN {local_curriculum} AS cc ON cc.program = p.id
            JOIN {local_cc_session_trainers} AS lcst ON lcst.programid = cc.program
            LEFT JOIN {local_cc_course_sessions} AS lccs ON lccs.programid = lcst.programid WHERE (lcst.trainerid = $USER->id OR lccs.trainerid = $USER->id)";
        if(!empty($filtervalues->filterdata)){
            $search_value = $filtervalues->filterdata;
            $formsql .= " and ((p.fullname LIKE '%$search_value%' ) 
                            OR (p.duration LIKE '%$search_value%') 
                            OR (p.year LIKE '%$search_value%')
                            OR (p.shortcode LIKE '%$search_value%')
                            )";
        }
        $queryparam = array();
        $totalprograms = $DB->count_records_sql($countsql.$formsql); 
        $formsql .= " order by p.id desc";
        $myprograms = $DB->get_records_sql($selectsql.$formsql, $queryparam, $stable->start, $stable->length);
        if ($myprograms) {
          $data = array();
          foreach ($myprograms as $myprogram) {
            $list = array();
            $semcourses = $DB->get_records_sql("SELECT c.id, c.fullname
            FROM {course} AS c
            JOIN {local_cc_semester_courses} AS lcsc ON lcsc.programid = $myprogram->id
            WHERE lcsc.courseid = c.id");
            $courselist = array();
            foreach ($semcourses as $key => $semcourse) {
                $eachcourselist = array();
                $eachcourselist['coursename'] = $semcourse->fullname;
                $coursecontext = context_course::instance($semcourse->id);
                $users = get_enrolled_users($coursecontext);
                $eachcourselist['enrolledusers'] = count($users);
                $eachcourselist['courseid'] = $semcourse->id;
                $courselist[] = $eachcourselist;
            }
            $list['fullnametitle'] = $myprogram->fullname;
            if($myprogram->duration_format == 'M'){
                $list['duration'] = $myprogram->duration.' Months';
            }else{
                $list['duration'] = $myprogram->duration.' Years';
            }            
            if($myprogram->program_logo){
                $prgmimg_urlpath = program_logo($myprogram->program_logo);
            }
            if(!empty($prgmimg_urlpath)){
                $list['program_imgpath'] = $prgmimg_urlpath->out();
            }else{
                $list['program_imgpath'] = null;
            }

            $list['shortcode'] = $myprogram->shortcode;
            $list['programid'] = $myprogram->id;
            $list['programcourses'] = $courselist;
            if (strlen($myprogram->fullname) >= 22) {
                $myprogram_fullname = substr($myprogram->fullname, 0, 22) . '...';
            } else {
                $myprogram_fullname = $myprogram->fullname;
            }
            $list['fullname'] =  $myprogram_fullname;
            $list['program_url'] = $CFG->wwwroot.'/local/program/view.php?ccid='.$myprogram->curriculumid.'&prgid='.$myprogram->id;
            $list['syllabus_url'] = $CFG->wwwroot . '/local/program/syllabus.php?pid='.$myprogram->id.'&ccid='.$myprogram->curriculumid;
            $data[] = $list;
          }
        }
        return array('count' => $totalprograms, 'data' => $data); 
    }

    public static function faculty_sessions($stable,$filtervalues) {
        global $DB, $USER, $CFG, $PAGE;
        $systemcontext = \context_system::instance();
        $countsql = "SELECT COUNT(bcs.id) ";
        $fromsql = "SELECT bcs.*, lr.name as room, class.classname";

        $sql = " FROM {local_cc_course_sessions} AS bcs
                LEFT JOIN {user} AS u ON u.id = bcs.trainerid
                LEFT JOIN {local_location_room} AS lr ON lr.id = bcs.roomid 
                LEFT JOIN {local_cc_semester_classrooms} AS class ON class.id = bcs.bclcid";
        $sql .= " WHERE 1 = 1 AND bcs.courseid = 0";

        $studentroleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
        $isStudent = user_has_role_assignment($USER->id,$studentroleid);
        if (has_capability('local/program:takesessionattendance', $systemcontext) && !is_siteadmin() && !has_capability('local/program:manageprogram', $systemcontext) && !has_capability('local/costcenter:manage_owndepartments', $systemcontext)){
            $sql .= " AND bcs.trainerid = $USER->id ";
        }

        if(!empty($filtervalues->filterdata)){
           $sql .= " AND bcs.name LIKE '%%$filtervalues->filterdata%%'";
        }

        $queryparam = array();
        $sessionscount = $DB->count_records_sql($countsql . $sql);
    
        $sql .= " order by bcs.id desc";
        $mysessions = $DB->get_records_sql($fromsql.$sql, $queryparam, $stable->start, $stable->length);
        if ($mysessions) {
          $data = array();
          foreach ($mysessions as $mysession) {
            $list = array();
            $list['sessionid'] = $mysession->id;
            $list['nametitle'] = $mysession->name;
            $list['room'] = $mysession->room;
            $list['time'] = date('d-m-Y H:i:s',$mysession->timestart);
            if (strlen($mysession->name) >= 22) {
                $mysession_fullname = substr($mysession->name, 0, 22) . '...';
            } else {
                $mysession_fullname = $mysession->name;
            }

            $competedcount = $DB->count_records('local_cc_session_signups',array('sessionid' => $mysession->id, 'completion_status' => 1));
            $enrolledcount = $DB->count_records('local_cc_session_signups',array('sessionid' => $mysession->id));
            if($mysession->attendance_status == 1){
                $status = 'Completed';
            }else{
                $status = 'Pending';
            }
            $list['attendedusers'] =  $competedcount.'/'.$enrolledcount;
            $list['status'] =  $status;
            $list['name'] =  $mysession_fullname;
            $list['program_url'] = $CFG->wwwroot.'/local/program/attendance.php?ccid='.$mysession->curriculumid.'&semesterid='.$mysession->semesterid.'&bclcid='.$mysession->bclcid.'&sid='.$mysession->id.'&programid='.$mysession->programid.'&yearid='.$mysession->yearid.'&ccses_action=class_sessions';
            $data[] = $list;
          }
        }
        return array('count' => $sessionscount, 'data' => $data); 
    }
} // end of class

