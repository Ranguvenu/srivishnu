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
 * local_program LIB
 *
 * @package    local_program
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courses;
defined('MOODLE_INTERNAL') || die;
use user_course_details;
use stdclass;
use moodle_url;
use html_writer;
use notifications;

class notificationemails {

    function course_emaillogs($type,$dataobj,$touserid,$fromuserid,$string=NULL){
        global $DB, $USER, $CFG, $PAGE;
        require_once($CFG->dirroot.'/local/notifications/lib.php');
        require_once($CFG->dirroot.'/local/includes.php');
        
        $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));
        $singleuser = $touserid ;
        $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser");
        $get_notifications_emp = $DB->get_record('local_notification_info', array('notificationid'=>$get_notification_type->id,'active'=>1,'costcenterid'=>$costcenter->open_costcenterid),'id,body,adminbody');
        $from = $DB->get_record('user', array('id'=>$USER->id));
        $data=$DB->get_record('course',array('id'=>$dataobj));
        if($data->open_departmentid){
            $department=$DB->get_field('local_costcenter','fullname',array('id'=>$data->open_departmentid));
        }else{
            $department='--';
        }
        
        $includes = new user_course_details();
        $courseimage = $includes->course_summary_files($data);
        $dataobj = new stdclass();
        $dataobj->course_title = $data->fullname;
        if($data->startdate){
            $dataobj->course_enrolstartdate = date("d-m-Y", $data->startdate);
        }else{
            $dataobj->course_enrolstartdate = 'N/A';
        }
        if($data->enddate){
            $dataobj->course_enrolenddate = date("d-m-Y", $data->enddate);
        }else{
            $dataobj->course_enrolenddate = 'N/A';
        }
        $adduser = $DB->get_record('user',array('id'=>$singleuser));
        if($data->open_coursecompletiondays){
            $dataobj->course_completiondays = $data->open_coursecompletiondays;
        }else{
            $dataobj->course_completiondays = 'N/A';   
        }
        $dataobj->course_department = $department;
        $url = new moodle_url($CFG->wwwroot.'/course/view.php?id='.$data->id);
        if($data->summary){
            $dataobj->course_description = $data->summary;
        }else{
            $dataobj->course_description = 'N/A';
        }
        $dataobj->course_url = '<a href='.$url.'>'.$url.'</a>';
        if($data->open_coursecreator){
            $dataobj->course_creator = $data->open_coursecreator;
        }else{
            $dataobj->course_creator = 'N/A';
        }
        if($type == 'course_complete'){
            $completed_date = $DB->get_record_sql("SELECT cc.timecompleted FROM {course_completions} cc where cc.timecompleted IS NOT NULL and cc.userid = $USER->id and cc.course = $data->id");
            if($completed_date){
                $dataobj->course_completiondate = $completed_date;
            }else{
                $dataobj->course_completiondate = 'Not Completed'; 
            }
        }
        $dataobj->course_image = html_writer::img($courseimage, $data->fullname,array());
        $dataobj->enroluser_fullname = $adduser->firstname.' '.$adduser->lastname;
        $dataobj->enroluser_email = $adduser->email;
        $dataobj->adminbody = NULL;
        $dataobj->body = $get_notifications_emp->body;
        $touserid = $singleuser;
        $fromuserid = $fromuserid;

        $notifications_lib = new notifications();
        $emailtype = $type;               
        $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid);
    }
}