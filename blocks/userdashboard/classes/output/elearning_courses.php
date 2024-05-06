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

namespace block_userdashboard\output;

use renderable;
use renderer_base;
use templatable;
use context_course;
use stdClass;
use html_writer;
use html_table;
use core_completion\progress;
use block_userdashboard\lib\elearning_courses  as courseslist_lib;
use block_userdashboard\includes\generic_content;
// use block_userdashboard\includes\user_course_details as user_course_details;



class elearning_courses implements renderable, templatable {

    //-----hold the courselist inprogress or completed 
    private $courseslist;

    private $subtab='';

    private $elearningtemplate=0;

    private $filter_text='';

    private $filter='';

    public function __construct($filter,$filter_text=''){
       
        switch ($filter){
            case 'inprogress':
                    $this->courseslist = courseslist_lib::inprogress_coursenames($filter_text);
                    $this->subtab='elearning_inprogress';
                break;
            case 'completed':
                    $this->courseslist = courseslist_lib::completed_coursenames($filter_text);
                    $this->subtab='elearning_completed';
                break;

        }
        $this->filter = $filter;  
        $this->filter_text = $filter_text;
        $this->elearningtemplate=1;

    } // end of the function
    

    


    /**
     * Export the data.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $USER, $CFG;
       
        $data = new stdClass(); $courses_active = ''; $tabs = '';  
        $data->inprogress_elearning = array();    
        // $pending_elearning = courseslist_lib::pastdue_coursenames($this->filter_text);
        // $completed = courseslist_lib::completed_coursenames($this->filter_text);
        // $inprogresscount = count($this->courseslist);
        // $completedcount = count($completed);
        $total = count($this->courseslist);
        // if ($courseslist == '') {
        //     $courseslist = null;
        // }

        // if ($pending_elearning == '') {
        //     $pending_elearning = null;
        // }

        // if ($completed == '') {
        //     $completed = null;
        // }
        $data->course_count_view =0;
        $data->total =$total;
        $data->index = count($this->courseslist)-1;
        $data->inprogresscount= count($this->courseslist);
        // $data->completedcount = count($completed);
        $data->functionname ='elearning_courses';
        $data->subtab=$this->subtab;
        $data->elearningtemplate=$this->elearningtemplate;
        $data->filter = $this->filter; 
        $data->filter_text = $this->filter_text;
        $courses_view_count = count($this->courseslist);
        $data->courses_view_count = $courses_view_count;
        $data->view_more_url = $CFG->wwwroot.'/blocks/userdashboard/userdashboard_courses.php?tab=elearning&subtab='.explode('_',$this->subtab)[1];
       // if($courses_view_count >= 1)
            $data->enableslider = 1;
       /* else    
            $data->enableslider = 0;*/
        
        if (!empty($this->courseslist)) 
            $data->inprogress_elearning_available =1;
        else
            $data->inprogress_elearning_available =0;

        if (!empty($this->courseslist)) {

            $data->course_count_view = generic_content::get_coursecount_class($this->courseslist);

            foreach ($this->courseslist as $inprogress_programs) {
                $onerow=array();
              //  $mycourses = $this->get_onlineusers_program_courses($inprogress_coursename->programid,$USER->id);
               /* $onerow['bootcampdescription'] = $this->get_coursesummary($inprogress_programs);
                $onerow['coursedetails'] = $mycourses;
                $onerow['inprogress_coursename'] = $inprogress_coursename;
                //---------get course fullname-----
                $onerow['programname'] = $inprogress_coursename->programname;*/
                // $onerow['inprogress_coursename_fullname'] = $this->get_coursefullname($inprogress_coursename);
                $onerow['bootcamp_fullname'] = $inprogress_programs->fullname;
                $onerow['courseid'] = $inprogress_programs->id;
                $onerow['enrolledusers'] = $this->getenrolledusers($inprogress_programs->id);
                 $onerow['inprogress_bootcamp_fullname'] = $this->get_coursefullname($inprogress_programs);
                $onerow['bootcamp_url'] = $CFG->wwwroot.'/course/view.php?id='.$inprogress_programs->id;

             
                array_push($data->inprogress_elearning, $onerow);
                
            } // end of foreach 

        } // end of if condition     
       //  print_object($data->inprogress_elearning);
        $data->inprogress_elearning= json_encode($data->inprogress_elearning);
        $data->menu_heading = '';// get_string('regular_programs','block_userdashboard');
        $data->nodata_string = get_string('nocoursesavailable','block_userdashboard');
       // print_object($data);
        return $data;
    } // end of export_for_template function

    public function getenrolledusers($id){
        global $DB,$CFG;
        $coursecontext = context_course::instance($id);
        $users = get_enrolled_users($coursecontext);
        return count($users);
    }


    public function get_onlineusers_program_courses($programid,$userid){
        global $DB, $CFG;
        $onlinecoursesql = "SELECT lc.*
            FROM {local_courseenrolments} AS lc WHERE lc.programid=:programid AND lc.mdluserid = :userid";
        $onlinecourses = $DB->get_records_sql($onlinecoursesql, array('programid' => $programid,'userid' => $userid));
        
        $table = new html_table();
        $table->id = "onlineusers_programs_courses";

        $table->head = array(get_string('subject', 'block_userdashboard'),get_string('instructor', 'block_userdashboard'),get_string('actions', 'block_userdashboard'));
        $instructorid = $DB->get_field('role', 'id', array('shortname' => 'faculty'));
        if(!empty($onlinecourses)){
            $data = array();
            foreach($onlinecourses AS $course){
                $row = array();
                if($instructorid){
                    $instructorsql = "SELECT u.id, concat(u.firstname,' ',u.lastname) as facultyname 
                    FROM {user} AS u 
                    JOIN {role_assignments} AS ra ON ra.userid = u.id
                    JOIN {context} AS c ON c.contextlevel = 50 AND c.id = ra.contextid
                    WHERE c.instanceid = :courseid AND ra.roleid=:instructorid ";

                    $instructorsdata = $DB->get_records_sql_menu($instructorsql, array('courseid' => $course->courseid, 'instructorid' => $instructorid));
                    $instructors = implode(',', $instructorsdata);
                    if($instructors){
                        $faculty = $instructors;
                    }else{
                        $faculty = 'Not Assigned';
                    }
                }
                $row[] = "<div class='col-md-4'>".$course->coursename."</div>";
                $row[] = "<div class='col-md-4'>".$faculty."</div>";
                $row[] = "<div class='col-md-4'>".html_writer::tag('a', 'View', array('href' =>$CFG->wwwroot. '/course/view.php?id='.$course->courseid, 'class'=>'btn'))."</div>";
                $data[] = implode('', $row);
            }
        }else{
            $data = '<div class="alert alert-info w-full pull-left text-xs-center"></div>';
        }
        $table->data[] = $data;
        return html_writer::table($table);
    }
    


    private function get_coursesummary($course_record){   

        $coursesummary = strip_tags($course_record->summary);
        $summarystring = strlen($coursesummary) > 100 ? substr($coursesummary, 0, 100)."..." : $coursesummary;
        $coursesummary = $summarystring;
        if(empty($coursesummary)){
            $coursesummary = '<span class="alert alert-info pull-left">'.get_string('nodecscriptionprovided','block_userdashboard').'</span>';
        }

        return $coursesummary;
    } // end of function


    private function get_coursefullname($inprogress_coursename){

        $course_fullname = $inprogress_coursename->fullname;
        if (strlen($course_fullname) >= 20) {
            $inprogress_coursename_fullname = substr($inprogress_coursename->fullname, 0, 20) . '...';
        } else {
            $inprogress_coursename_fullname = $inprogress_coursename->fullname;
        }

        return $inprogress_coursename_fullname;
    } // end of function



} // end of class
