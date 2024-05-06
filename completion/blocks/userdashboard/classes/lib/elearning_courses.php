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
 * @package    block_userdashboard
 * @copyright  2018 hemalatha c arun <hemalatha@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_userdashboard\lib;

use renderable;
use renderer_base;
use templatable;
use context_course;
use stdClass;

// $myprogram = new \block_myprogram\programlib($USER);
        // $test = $myprogram->block_content();
class elearning_courses{

    /******Function to the show the inprogress course names in the E-learning Tab********/
    public static function inprogress_coursenames($filter_text='') {
       global $DB, $USER;  

          $completedsql = "SELECT c.id FROM {course} c JOIN {enrol} e ON e.courseid = c.id JOIN {user_enrolments} ue ON e.id = ue.enrolid";
         $completedsql .= " JOIN {course_completions} cc ON cc.course = c.id WHERE cc.userid = ".$USER->id." AND timecompleted IS NOT NULL";
          $completedcourses = $DB->get_records_sql($completedsql);
          foreach($completedcourses as $courses){

              $id = $courses->id;
              $complcourse[] = $id;
          }  
         $completedcourseid = implode($complcourse,',');
        
         $sql = "SELECT c.* FROM {course} c JOIN {enrol} e ON e.courseid = c.id JOIN {user_enrolments} ue ON e.id = ue.enrolid";
         $sql .=" WHERE c.forpurchaseindividually IS NOT NULL AND ue.userid =".$USER->id; 
          if($completedcourseid){
             $sql .= " AND c.id NOT IN (".$completedcourseid.")";
         }
       
          $allcourses =  $DB->get_records_sql($sql);
     
            if(!empty($filter_text)){
               $sql .= " AND c.fullname LIKE '%%$filter_text%%'";
            }
           
             $sql .= " ORDER BY c.id desc";
            $inprogresscourses = $DB->get_records_sql($sql);
         
        return $inprogresscourses;
    }
       public static function completed_coursenames($filter_text='') {
        global $DB, $USER;    
       
         $sql = "SELECT c.* FROM {course} c JOIN {enrol} e ON e.courseid = c.id JOIN {user_enrolments} ue ON e.id = ue.enrolid";
         $sql .= " JOIN {course_completions} cc ON cc.course = c.id WHERE cc.userid = ".$USER->id." AND timecompleted IS NOT NULL AND c.forpurchaseindividually IS NOT NULL";    

            if(!empty($filter_text)){
               $sql .= " AND c.fullname LIKE '%%$filter_text%%'";
            }
          
             $sql .= " ORDER BY c.id desc";
            $completed_bootcamps = $DB->get_records_sql($sql);

        return $completed_bootcamps;
    }

    public static function my_courses($stable,$filtervalues) {
        global $DB, $USER, $CFG;  
         $countsql = "SELECT COUNT(c.id) ";
         $selectsql = "SELECT c.* ";

         if(!empty($filtervalues) && $filtervalues->status == 'completed'){
              $fromsql .= " FROM {course} c JOIN {enrol} e ON e.courseid = c.id JOIN {user_enrolments} ue ON e.id = ue.enrolid
                    JOIN {course_completions} cc ON cc.course = c.id WHERE cc.userid = ".$USER->id." AND timecompleted IS NOT NULL AND c.forpurchaseindividually IS NOT NULL"; 
         }else{
              $completedsql = "SELECT c.id FROM {course} c JOIN {enrol} e ON e.courseid = c.id JOIN {user_enrolments} ue ON e.id = ue.enrolid";
              $completedsql .= " JOIN {course_completions} cc ON cc.course = c.id WHERE cc.userid = ".$USER->id." AND timecompleted IS NOT NULL";
              $completedcourses = $DB->get_records_sql($completedsql);
              foreach($completedcourses as $courses){
                  $id = $courses->id;
                  $complcourse[] = $id;
              }  
              $completedcourseid = implode($complcourse,',');
              
              $fromsql = " FROM {course} c JOIN {enrol} e ON e.courseid = c.id JOIN {user_enrolments} ue ON e.id = ue.enrolid";
              $fromsql .=" WHERE c.forpurchaseindividually IS NOT NULL AND ue.userid =".$USER->id; 
              if($completedcourseid){
                  $fromsql .= " AND c.id NOT IN (".$completedcourseid.")";
              }

         }
         
          if(!empty($filtervalues->filterdata)){
             $fromsql .= " AND c.fullname LIKE '%%$filtervalues->filterdata%%'";
          }
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
