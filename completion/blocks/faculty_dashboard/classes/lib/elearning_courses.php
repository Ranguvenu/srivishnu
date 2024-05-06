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
 * elearning  courses
 *
 * @package    block_faculty_dashboard
 * @copyright  2018 Sarath kumar Setti <sarath@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_faculty_dashboard\lib;

use renderable;
use renderer_base;
use templatable;
use context_course;
use stdClass;

class elearning_courses{
    public static function faculty_courses($stable,$filtervalues) {
        global $DB, $USER, $CFG;  
         $countsql = "SELECT COUNT(c.id) ";
         $selectsql = "SELECT c.* ";

         $fromsql .= " FROM {course} c JOIN {context} as cnx ON cnx.instanceid = c.id JOIN {role_assignments} as ra ON ra.contextid = cnx.id JOIN {role} as r ON r.id = ra.roleid WHERE r.shortname = 'faculty' AND cnx.contextlevel = 50 AND c.forpurchaseindividually = 1 AND ra.userid = ".$USER->id; 
         
          if(!empty($filtervalues->filterdata)){
             $fromsql .= " AND c.fullname LIKE '%%$filtervalues->filterdata%%'";
          }
          // echo $countsql.$fromsql;
          $queryparam = array();
          $coursescount =  $DB->count_records_sql($countsql.$fromsql);
          $fromsql .= " ORDER BY c.id desc";
          $courses = $DB->get_records_sql($selectsql.$fromsql, $queryparam, $stable->start, $stable->length);
         if ($courses) {
            $data = array();
            foreach ($courses as $course) {
                $list = array();
                $list['bootcamp_fullname'] = $course->fullname;
                $list['courseid'] = $course->id;
                $coursecontext = context_course::instance($course->id);
                $users = get_enrolled_users($coursecontext);
                $list['enrolledusers'] = count($users);
                if (strlen($course->fullname) >= 22) {
                    $course_fullname = substr($course->fullname, 0, 22) . '...';
                } else {
                    $course_fullname = $course->fullname;
                }
                $list['inprogress_bootcamp_fullname'] = $course_fullname;
                $list['bootcamp_url'] = $CFG->wwwroot.'/course/view.php?id='.$course->id;
                $data[] = $list;
            }
         }
        return array('count' => $coursescount, 'data' => $data); 
    }

} // end of class
