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
 * local courses
 *
 * @package    local_courses
 * @copyright  2019 eAbyas <eAbyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace local_courses\local;

class user{
    
    public function user_profile_content($userid,$return = false,$start =0,$limit =5){
        global $OUTPUT;
        $returnobj = new \stdClass();
        $returnobj->coursesexist = 1;
        $records = $this->enrol_get_users_courses($userid,false,true,$start,$limit);
        $courses = $records['data'];
        
        $data = array();
        foreach ($courses as $course) {
            $coursesarray = array();
            $coursesarray["id"] = $course->id;
            $coursesarray["name"] = $course->fullname;
            $url = new \moodle_url('/course/view.php', array('id' => $course->id));
            $urllink = $url->out();
            $coursesarray["url"] = $urllink;
            $coursesummary = $course->summary;
            $coursesummary = strip_tags($coursesummary);
            $summarystring = strlen($coursesummary) > 250 ? substr($coursesummary, 0, 250)."..." : $coursesummary;
            $coursesarray["description"] = $summarystring;
            $coursesarray["percentage"] = round($this->user_course_completion_progress($course->id,$userid));
            $coursesarray['startdate'] = '';
            $coursesarray['enddate'] = '';
            $coursesarray['validtill'] = '';
            $coursesarray['status'] = '';
            $data[] = $coursesarray;
        }

        $returnobj->sequence = 0;
        $returnobj->count = $records['count'];
        $returnobj->divid = 'user_courses';
        $returnobj->moduletype = 'courses';
        $returnobj->targetID = 'display_classroom';
        $returnobj->userid = $userid;
        $returnobj->string = get_string('courses', 'local_users');
        $returnobj->navdata = $data;
        return $returnobj;
    }

    /**
     * Description: User Course completion progress
     * @param  INT $courseid course id whose completed percentage to be fetched
     * @param  INT $userid   userid whose completed course prcentage to be fetched
     * @return INT           percentage of completion.
     */
    public function user_course_completion_progress($courseid, $userid) {
        global $DB, $USER, $CFG;
        if(empty($courseid) || empty($userid)){
            return false;
        }
        
        $sql="SELECT id from {course_completions} where course= ? and userid= ? and  timecompleted IS NOT NULL";
        $completionenabled=$DB->get_record_sql($sql, [$courseid, $userid]);
        if($completionenabled ==''){
        $total_activity_count = $this->total_course_activities($courseid);
        $completed_activity_count = $this->user_course_completed_activities($courseid, $userid);
            if($total_activity_count>0 && $completed_activity_count>0){
            	$course_completion_percent = $completed_activity_count/$total_activity_count*100;
            }
        }else{
            $course_completion_percent=100;
        }
        return $course_completion_percent;
    }

    /**
     * Description: User Course total Activities count
     * @param INT $courseid course id whose total activities count to be fetched
     * @return INT count of total activities
     */
    public function total_course_activities($courseid) {
        global $DB, $USER, $CFG;
        if(empty($courseid)){
            return false;
        }
        $sql="SELECT COUNT(ccc.id) as totalactivities FROM {course_modules} ccc WHERE ccc.course=?";
        $totalactivitycount = $DB->get_record_sql($sql, [$courseid]);
        $out = $totalactivitycount->totalactivities;
        return $out;
    }
    /**
     * Description: User Course Completed Activities count
     * @param  INT $courseid course id whose completed activities count to be fetched
     * @param  INT $userid   userid whose completed activities count to be fetched
     * @return INT           count of completed activities
     */
    public function user_course_completed_activities($courseid, $userid) {
        global $DB, $USER, $CFG;
        if(empty($courseid) || empty($userid)){
            return false;
        }
        $sql="SELECT count(cc.id) as completedact from {course_completion_criteria} ccc JOIN {course_modules_completion} cc ON cc.coursemoduleid = ccc.moduleinstance where ccc.course = ? and cc.userid= ? and cc.completionstate = 1 ";
        $completioncount = $DB->get_record_sql($sql, [$courseid, $userid]);
        $out = $completioncount->completedact;
        return $out;
    }
    public function enrol_get_users_courses($userid, $count =false, $limityesno = false, $start = 0, $limit = 5) {
        global $DB;
        $countsql = "SELECT count((course.id)) ";
        $coursessql = "SELECT distinct(course.id), course.fullname,course.shortname, course.summary,ue.timecreated as enrolldate ";

        $fromsql = "FROM {course} AS course
                    JOIN {enrol} AS e ON course.id = e.courseid AND e.enrol IN('self','manual','program')
                    JOIN {user_enrolments} ue ON e.id = ue.enrolid
                    WHERE ue.userid = ? AND course.id>1 ORDER BY ue.id desc";
        if ($limityesno)
            $records = $DB->get_records_sql($coursessql.$fromsql, [$userid], $start, $limit);
        else
        $records = $DB->get_records_sql($coursessql.$fromsql, [$userid]);

        $total = $DB->count_records_sql($countsql.$fromsql, [$userid]);

        return array('data'=>$records, 'count'=>$total);
    }
}