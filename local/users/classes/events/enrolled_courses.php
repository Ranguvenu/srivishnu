<?php
namespace local_users\events;
require_once(dirname(__FILE__) . '/../../../../config.php');

class enrolled_courses{
    /**
     * [display_courses description]
     * @param  [int]  $id 
     * @param  integer $start  [description]
     * @param  integer $length [description]
     * @param  string  $search [description]
     * @param  integer $total  [description]
     * @return [object] of courses data.
     */
    public function display_courses($id,$start = 0, $length = 0, $search = '', $total = 0){
        global $DB, $OUTPUT,$USER;
        $sql = "SELECT c.id,c.fullname,ccl.timecompleted as timecompleted,ccl.userid as userid
                                        FROM {course} c                                       
                                        LEFT JOIN {course_completions} ccl ON ccl.course = c.id
                                        JOIN {enrol} e ON c.id = e.courseid
                                        JOIN {user_enrolments} ue ON e.id = ue.enrolid
                                        WHERE ue.userid = $id";                               
        if($total == 0){
            if ( $search != "" ){
                $sql .= " and ((c.fullname LIKE '%".$search."%')  ) ";
            }
            
            if ( isset( $start ) && $length != -1){
                $sql .="  LIMIT ".$start .", ".$length;
            }
        }elseif($total == 1){
            if ( $search != "" ){
                $sql .= " and ((c.fullname LIKE '%".$search."%')  ) ";
            }
        }
        $displaycourses = $DB->get_records_sql($sql);
      
        return $displaycourses;
    }
}