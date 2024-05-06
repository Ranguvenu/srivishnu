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
 * local_curriculum LIB
 *
 * @package    local_curriculum
 * @copyright  2018 Arun Kumar M <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_program;
defined('MOODLE_INTERNAL') || die;
use stdclass;
use user_course_details;
use moodle_url;
use html_writer;
use notifications;

class programnotifications_emails { //curriculumnotifications_emails

    function curriculum_emaillogs($type,$dataobj,$touserid,$fromuserid,$string=NULL){
        global $DB, $USER, $CFG, $PAGE;
        require_once($CFG->dirroot.'/local/notifications/lib.php');
        require_once($CFG->dirroot.'/local/includes.php');

        switch ($type) {
            case 'curriculum_enrol':
            $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));

            $singleuser=new stdClass();
            $singleuser->id= $touserid ;
            $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser->id");
            $costcenter_trainer = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");

            $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj,moduleid)");

            if(!$get_notifications_emp){
                $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and moduleid IS NULL");
            }
            if($get_notifications_emp){
                $from = $DB->get_record('user', array('id'=>$USER->id));
                $data=$DB->get_record('local_curriculum',array('id'=>$dataobj));
                $dataobj = new stdclass();

                $creater = $DB->get_record('user',array('id'=>$data->usercreated));
                $dataobj->curriculum_name = $data->name;
                $organization = $DB->get_field('local_costcenter',  'fullname',  array('id'=>$data->costcenter));
                $dataobj->curriculum_organization = $organization;
                $program = $DB->get_field('local_program',  'fullname',  array('id'=>$data->program));
                $dataobj->curriculum_program = $program;

                $dataobj->curriculum_startdate = date('d-m-Y',$data->startdate);
                $dataobj->curriculum_enddate = date('d-m-Y',$data->enddate);
                $dataobj->curriculum_creater = $creater->firstname.' '.$creater->lastname;
                $adduser = $DB->get_record('user',array('id'=>$singleuser->id));
                if($string == NULL){
                    $dataobj->curriculum_enroluserfulname = $adduser->firstname.' '.$adduser->lastname;
                }elseif($string == 'trainer'){
                    $dataobj->curriculum_enroluserfulname = $adduser->firstname.' '.$adduser->lastname.' '.'is enrolled as Trainer';
                }
                $url = new moodle_url($CFG->wwwroot.'/local/program/view.php?ccid='.$data->id.'&prgid='.$data->program);
                $dataobj->curriculum_link = '<a href='.$url.'>'.$url.'</a>';
                $dataobj->curriculum_enroluseremail = $adduser->email;

                // $dataobj->curriculum_completiondate = $data->description;
                $dataobj->adminbody = NULL;
                $dataobj->body = $get_notifications_emp->body;
                $touserid = $singleuser->id;
                $fromuserid = $USER->id;
                $notifications_lib = new notifications();
                $emailtype = $type;
                $dataobj->moduletype="curriculum";
                $dataobj->moduleid=$data->id;

                $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,0,0,0,$get_notifications_emp->id);
            }

            break;
        case 'curriculum_unenroll':
            $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));

            $singleuser=new stdClass();
            $singleuser->id= $touserid ;
            $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser->id");
            $costcenter_trainer = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");

            $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj,moduleid)");

            if(!$get_notifications_emp){
                $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and moduleid IS NULL");
            }
            if($get_notifications_emp){
                $from = $DB->get_record('user', array('id'=>$USER->id));
                $data=$DB->get_record('local_curriculum',array('id'=>$dataobj));

                $creater = $DB->get_record('user',array('id'=>$data->usercreated));
                $dataobj = new stdclass();
                $dataobj->curriculum_name = $data->name;
                $organization = $DB->get_field('local_costcenter',  'fullname',  array('id'=>$data->costcenter));
                $dataobj->curriculum_organization = $organization;
                $program = $DB->get_field('local_program',  'fullname',  array('id'=>$data->program));
                $dataobj->curriculum_program = $program;

                $dataobj->curriculum_startdate = date('d-m-Y',$data->startdate);
                $dataobj->curriculum_enddate = date('d-m-Y',$data->enddate);
                $dataobj->curriculum_creater = $creater->firstname.' '.$creater->lastname;
                $adduser = $DB->get_record('user',array('id'=>$singleuser->id));
                if($string == NULL){
                    $dataobj->curriculum_enroluserfulname = $adduser->firstname.' '.$adduser->lastname;
                }elseif($string == 'trainer'){
                    $dataobj->curriculum_enroluserfulname = $adduser->firstname.' '.$adduser->lastname.' '.'Trainer is unenrolled';
                }
                $url = new moodle_url($CFG->wwwroot.'/local/program/view.php?ccid='.$data->id.'&prgid='.$data->program);
                $dataobj->curriculum_link = '<a href='.$url.'>'.$url.'</a>';
                $dataobj->curriculum_enroluseremail = $adduser->email;

                // $dataobj->curriculum_completiondate = $data->description;
                $dataobj->adminbody = NULL;
                $dataobj->body = $get_notifications_emp->body;
                $touserid = $singleuser->id;
                $fromuserid = $USER->id;
                //print_object($dataobj);
                $notifications_lib = new notifications();
                $emailtype = $type;
                $dataobj->moduletype="curriculum";
                $dataobj->moduleid=$data->id;
                $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,0,0,0,$get_notifications_emp->id);
            }

            break;
        case 'curriculum_completion':
            $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));

            $singleuser=new stdClass();
            $singleuser->id= $touserid ;
            $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser->id");
            $costcenter_trainer = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");

            $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj,moduleid)");

            if(!$get_notifications_emp){
                $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and moduleid IS NULL");
            }
            if($get_notifications_emp){
                $from = $DB->get_record('user', array('id'=>$USER->id));
                $data=$DB->get_record('local_curriculum',array('id'=>$dataobj));
                $dataobj = new stdclass();
                $creater = $DB->get_record('user',array('id'=>$data->usercreated));
                $dataobj->curriculum_name = $data->name;
                $organization = $DB->get_field('local_costcenter',  'fullname',  array('id'=>$data->costcenter));
                $dataobj->curriculum_organization = $organization;
                $program = $DB->get_field('local_program',  'fullname',  array('id'=>$data->program));
                $dataobj->curriculum_program = $program;
                $dataobj->curriculum_startdate = date('d-m-Y',$data->startdate);
                $dataobj->curriculum_enddate = date('d-m-Y',$data->enddate);
                $dataobj->curriculum_creater = $creater->firstname.' '.$creater->lastname;
                $adduser = $DB->get_record('user',array('id'=>$singleuser->id));
                $dataobj->curriculum_enroluserfulname = $adduser->firstname.' '.$adduser->lastname;

                $url = new moodle_url($CFG->wwwroot.'/local/program/view.php?ccid='.$data->id.'&prgid='.$data->program);
                $dataobj->curriculum_link = '<a href='.$url.'>'.$url.'</a>';
                $dataobj->curriculum_enroluseremail = $adduser->email;
                $completiondate = $DB->get_field('local_curriculum_users', 'completiondate', array('curriculumid'=>$data->id, 'userid'=>$singleuser->id));
                $dataobj->curriculum_completiondate = date('d-m-Y',$completiondate);
                $dataobj->adminbody = NULL;
                $dataobj->body = $get_notifications_emp->body;
                $touserid = $singleuser->id;
                $fromuserid = $USER->id;

                $notifications_lib = new notifications();
                $emailtype = $type;
                $dataobj->moduletype="curriculum";
                $dataobj->moduleid=$data->id;
                $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,0,0,0,$get_notifications_emp->id);
            }
            break;
        case 'curriculum_semester_completion':
            $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));

            $singleuser=new stdClass();
            $singleuser->id= $touserid ;
            $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser->id");
            $costcenter_trainer = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");

            $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj,moduleid)");

            if(!$get_notifications_emp){
                $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and moduleid IS NULL");
            }
            if($get_notifications_emp){
                $from = $DB->get_record('user', array('id'=>$USER->id));
                $data=$DB->get_record('local_curriculum_semesters',array('id'=>$dataobj));
                $curriculum=$DB->get_record('local_curriculum',array('id'=>$data->curriculumid));

                $creater = $DB->get_record('user',array('id'=>$data->usercreated));
                $mailcontent = new stdclass();
                $mailcontent->curriculum_semester = $data->semester;
                $mailcontent->curriculum_name = $curriculum->name;

                $mailcontent->curriculum_semester_creater = $creater->firstname.' '.$creater->lastname;
                $adduser = $DB->get_record('user',array('id'=>$singleuser->id));
                $mailcontent->curriculum_enroluserfulname = $adduser->firstname.' '.$adduser->lastname;

                $url = new moodle_url($CFG->wwwroot.'/local/program/view.php?ccid='.$data->curriculumid.'&prgid='.$data->programid);
                $mailcontent->curriculum_link = '<a href='.$url.'>'.$url.'</a>';

                $mailcontent->curriculum_enroluseremail = $adduser->email;
                $completiondate = $DB->get_field('local_cc_semester_cmptl', 'completiondate', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->id, 'userid'=>$singleuser->id));
                $mailcontent->curriculum_semester_completiondate = date('d-m-Y',$completiondate);
                $mailcontent->adminbody = NULL;
                $mailcontent->body = $get_notifications_emp->body;
                $touserid = $singleuser->id;
                $fromuserid = $USER->id;
                //print_object($dataobj);
                $notifications_lib = new notifications();
                $emailtype = $type;
                $mailcontent->moduletype="curriculum_semester";
                $mailcontent->moduleid=$data->id;
                $notifications_lib->send_email_notification($emailtype, $mailcontent, $touserid, $fromuserid,0,0,0,$get_notifications_emp->id);
            }
            break;
        case 'curriculum_session_enrol':
            $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));

            $singleuser=new stdClass();
            $singleuser->id= $touserid;
            $curriculumid = $dataobj->curriculumid;
            $sessionid = $dataobj->sessionid;
            $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser->id");
            $costcenter_trainer = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");

            $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($curriculumid,moduleid)");

            if(!$get_notifications_emp){
                $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and moduleid IS NULL");
            }
            if($get_notifications_emp){
                $from = $DB->get_record('user', array('id'=>$USER->id));
                $data=$DB->get_record('local_cc_course_sessions',array('id'=>$sessionid));
                $curriculum=$DB->get_record('local_curriculum',array('id'=>$data->curriculumid));
                $dataobj = new stdclass();

                $creater = $DB->get_record('user',array('id'=>$data->usercreated));
                $dataobj->curriculum_name = $curriculum->name;
                $semester = $DB->get_field('local_curriculum_semesters',  'semester',  array('id'=>$data->semesterid));
                $dataobj->curriculum_semester = $semester;
                $courseid = $DB->get_field('local_cc_semester_courses',  'courseid',  array('id'=>$data->bclcid));
                $course = $DB->get_field('course',  'fullname',  array('id'=>$courseid));
                $dataobj->curriculum_course = $course;
                $dataobj->curriculum_startdate = date('d-m-Y',$curriculum->startdate);
                $dataobj->curriculum_enddate = date('d-m-Y',$curriculum->enddate);
                // $dataobj->curriculum_creater = $creater->firstname.' '.$creater->lastname;
                $dataobj->curriculum_session_name = $data->name;
                $adduser = $DB->get_record('user',array('id'=>$singleuser->id));
                if($string == NULL){
                    $dataobj->curriculum_session_username = $adduser->firstname.' '.$adduser->lastname;
                }elseif($string == 'trainer'){
                    $dataobj->curriculum_session_username = $adduser->firstname.' '.$adduser->lastname.' '.'is enrolled as Trainer';
                }
                $url = new moodle_url($CFG->wwwroot.'/local/program/sessions.php?bclcid='.$data->bclcid.'&semesterid='.$data->semesterid.'&ccid='.$data->curriculumid);
                $dataobj->curriculum_session_link = '<a href='.$url.'>'.$url.'</a>';
                $dataobj->curriculum_session_useremail = $adduser->email;
                $trainer = $DB->get_record('user',array('id'=>$data->trainerid));
                $dataobj->curriculum_session_trainername = fullname($trainer);
                $dataobj->curriculum_session_startdate = date('d-m-Y',$data->timestart);
                $dataobj->curriculum_session_enddate = date('d-m-Y',$data->timefinish);

                // $dataobj->curriculum_completiondate = $data->description;
                $dataobj->adminbody = NULL;
                $dataobj->body = $get_notifications_emp->body;
                $touserid = $singleuser->id;
                $fromuserid = $USER->id;
                $notifications_lib = new notifications();
                $emailtype = $type;
                $dataobj->moduletype="curriculum_sessions";
                $dataobj->moduleid=$data->id;

                $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,0,0,0,$get_notifications_emp->id);
            }

            break;
        case 'curriculum_session_reschedule':
            $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));

            $singleuser=new stdClass();
            $singleuser->id= $touserid;
            $curriculumid = $dataobj->curriculumid;
            $sessionid = $dataobj->sessionid;
            $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser->id");
            $costcenter_trainer = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");

            $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($curriculumid,moduleid)");

            if(!$get_notifications_emp){
                $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and moduleid IS NULL");
            }
            if($get_notifications_emp){
                $from = $DB->get_record('user', array('id'=>$USER->id));
                $data=$DB->get_record('local_cc_course_sessions',array('id'=>$sessionid));
                $curriculum=$DB->get_record('local_curriculum',array('id'=>$data->curriculumid));
                $dataobj = new stdclass();

                $creater = $DB->get_record('user',array('id'=>$data->usercreated));
                $dataobj->curriculum_name = $curriculum->name;
                $semester = $DB->get_field('local_curriculum_semesters',  'semester',  array('id'=>$data->semesterid));
                $dataobj->curriculum_semester = $semester;
                $courseid = $DB->get_field('local_cc_semester_courses',  'courseid',  array('id'=>$data->bclcid));
                $course = $DB->get_field('course',  'fullname',  array('id'=>$courseid));
                $dataobj->curriculum_course = $course;
                $dataobj->curriculum_startdate = date('d-m-Y',$curriculum->startdate);
                $dataobj->curriculum_enddate = date('d-m-Y',$curriculum->enddate);
                // $dataobj->curriculum_creater = $creater->firstname.' '.$creater->lastname;
                $dataobj->curriculum_session_name = $data->name;
                $adduser = $DB->get_record('user',array('id'=>$singleuser->id));
                if($string == NULL){
                    $dataobj->curriculum_session_username = $adduser->firstname.' '.$adduser->lastname;
                }elseif($string == 'trainer'){
                    $dataobj->curriculum_session_username = $adduser->firstname.' '.$adduser->lastname.' '.'is enrolled as Trainer';
                }
                $url = new moodle_url($CFG->wwwroot.'/local/program/sessions.php?bclcid='.$data->bclcid.'&semesterid='.$data->semesterid.'&ccid='.$data->curriculumid);
                $dataobj->curriculum_session_link = '<a href='.$url.'>'.$url.'</a>';
                $dataobj->curriculum_session_useremail = $adduser->email;
                $trainer = $DB->get_record('user',array('id'=>$data->trainerid));
                $dataobj->curriculum_session_trainername = fullname($trainer);
                $dataobj->curriculum_session_startdate = date('d-m-Y',$data->timestart);
                $dataobj->curriculum_session_enddate = date('d-m-Y',$data->timefinish);

                // $dataobj->curriculum_completiondate = $data->description;
                $dataobj->adminbody = NULL;
                $dataobj->body = $get_notifications_emp->body;
                $touserid = $singleuser->id;
                $fromuserid = $USER->id;
                $notifications_lib = new notifications();
                $emailtype = $type;
                $dataobj->moduletype="curriculum_sessions";
                $dataobj->moduleid=$data->id;

                $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,0,0,0,$get_notifications_emp->id);
            }

            break;
        case 'curriculum_session_cancel':
            $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));

            $singleuser=new stdClass();
            $singleuser->id= $touserid;
            $curriculumid = $dataobj->curriculumid;
            $sessionid = $dataobj->sessionid;
            $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser->id");
            $costcenter_trainer = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");

            $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($curriculumid,moduleid)");

            if(!$get_notifications_emp){
                $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and moduleid IS NULL");
            }
            if($get_notifications_emp){
                $from = $DB->get_record('user', array('id'=>$USER->id));
                $data=$DB->get_record('local_cc_course_sessions',array('id'=>$sessionid));
                $curriculum=$DB->get_record('local_curriculum',array('id'=>$data->curriculumid));
                $dataobj = new stdclass();

                $creater = $DB->get_record('user',array('id'=>$data->usercreated));
                $dataobj->curriculum_name = $curriculum->name;
                $semester = $DB->get_field('local_curriculum_semesters',  'semester',  array('id'=>$data->semesterid));
                $dataobj->curriculum_semester = $semester;
                $courseid = $DB->get_field('local_cc_semester_courses',  'courseid',  array('id'=>$data->bclcid));
                $course = $DB->get_field('course',  'fullname',  array('id'=>$courseid));
                $dataobj->curriculum_course = $course;
                $dataobj->curriculum_startdate = date('d-m-Y',$curriculum->startdate);
                $dataobj->curriculum_enddate = date('d-m-Y',$curriculum->enddate);
                // $dataobj->curriculum_creater = $creater->firstname.' '.$creater->lastname;
                $dataobj->curriculum_session_name = $data->name;
                $adduser = $DB->get_record('user',array('id'=>$singleuser->id));
                if($string == NULL){
                    $dataobj->curriculum_session_username = $adduser->firstname.' '.$adduser->lastname;
                }elseif($string == 'trainer'){
                    $dataobj->curriculum_session_username = $adduser->firstname.' '.$adduser->lastname.' '.'Trainer is Unenrolled/Cancel from this session';
                }
                $url = new moodle_url($CFG->wwwroot.'/local/program/sessions.php?bclcid='.$data->bclcid.'&semesterid='.$data->semesterid.'&ccid='.$data->curriculumid);
                $dataobj->curriculum_session_link = '<a href='.$url.'>'.$url.'</a>';
                $dataobj->curriculum_session_useremail = $adduser->email;
                $trainer = $DB->get_record('user',array('id'=>$data->trainerid));
                $dataobj->curriculum_session_trainername = fullname($trainer);
                $dataobj->curriculum_session_startdate = date('d-m-Y',$data->timestart);
                $dataobj->curriculum_session_enddate = date('d-m-Y',$data->timefinish);

                // $dataobj->curriculum_completiondate = $data->description;
                $dataobj->adminbody = NULL;
                $dataobj->body = $get_notifications_emp->body;
                $touserid = $singleuser->id;
                $fromuserid = $USER->id;
                $notifications_lib = new notifications();
                $emailtype = $type;
                $dataobj->moduletype="curriculum_sessions";
                $dataobj->moduleid=$data->id;
                $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,0,0,0,$get_notifications_emp->id);
            }

            break;
        case 'curriculum_session_attendance':
            $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));

            $singleuser=new stdClass();
            $singleuser->id= $touserid ;
            $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser->id");
            $costcenter_trainer = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");

            $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj,moduleid)");

            if(!$get_notifications_emp){
                $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and moduleid IS NULL");
            }
            if($get_notifications_emp){
                $from = $DB->get_record('user', array('id'=>$USER->id));
                $data=$DB->get_record('local_cc_course_sessions',array('id'=>$dataobj));
                $curriculum=$DB->get_record('local_curriculum',array('id'=>$data->curriculumid));
                $dataobj = new stdclass();

                $creater = $DB->get_record('user',array('id'=>$data->usercreated));
                $dataobj->curriculum_name = $curriculum->name;
                $semester = $DB->get_field('local_curriculum_semesters',  'semester',  array('id'=>$data->semesterid));
                $dataobj->curriculum_semester = $semester;
                $courseid = $DB->get_field('local_cc_semester_courses',  'courseid',  array('id'=>$data->bclcid));
                $course = $DB->get_field('course',  'fullname',  array('id'=>$courseid));
                $dataobj->curriculum_course = $course;
                $dataobj->curriculum_startdate = date('d-m-Y',$curriculum->startdate);
                $dataobj->curriculum_enddate = date('d-m-Y',$curriculum->enddate);
                // $dataobj->curriculum_creater = $creater->firstname.' '.$creater->lastname;
                $dataobj->curriculum_session_name = $data->name;
                $adduser = $DB->get_record('user',array('id'=>$singleuser->id));
                $dataobj->curriculum_session_username = $adduser->firstname.' '.$adduser->lastname;

                $url = new moodle_url($CFG->wwwroot.'/local/program/sessions.php?bclcid='.$data->bclcid.'&semesterid='.$data->semesterid.'&ccid='.$data->curriculumid);
                $dataobj->curriculum_session_link = '<a href='.$url.'>'.$url.'</a>';
                $dataobj->curriculum_session_useremail = $adduser->email;
                $trainer = $DB->get_record('user',array('id'=>$data->trainerid));
                $dataobj->curriculum_session_trainername = fullname($trainer);
                $dataobj->curriculum_session_startdate = date('d-m-Y',$data->timestart);
                $dataobj->curriculum_session_enddate = date('d-m-Y',$data->timefinish);
                $completion_status = $DB->get_field('local_cc_session_signups', 'completion_status', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->semesterid, 'bclcid'=>$data->bclcid, 'sessionid'=>$data->id, 'userid'=>$singleuser->id));
                if($completion_status == SESSION_PRESENT) {
                    $dataobj->curriculum_session_attendance = 'Present';
                } else {
                    $dataobj->curriculum_session_attendance = 'Absent';
                }

                $dataobj->adminbody = NULL;
                $dataobj->body = $get_notifications_emp->body;
                $touserid = $singleuser->id;
                $fromuserid = $USER->id;
                $notifications_lib = new notifications();
                $emailtype = $type;
                $dataobj->moduletype="curriculum_sessions";
                $dataobj->moduleid=$data->id;
                $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,0,0,0,$get_notifications_emp->id);
            }
            break;

        case 'curriculum_session_completion':
            $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));

            $singleuser=new stdClass();
            $singleuser->id= $touserid ;
            $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser->id");
            $costcenter_trainer = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");

            $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj,moduleid)");

            if(!$get_notifications_emp){
                $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and moduleid IS NULL");
            }
            if($get_notifications_emp){
                $from = $DB->get_record('user', array('id'=>$USER->id));
                $data=$DB->get_record('local_cc_course_sessions',array('id'=>$dataobj));
                $curriculum=$DB->get_record('local_curriculum',array('id'=>$data->curriculumid));
                $dataobj = new stdclass();

                $creater = $DB->get_record('user',array('id'=>$data->usercreated));
                $dataobj->curriculum_name = $curriculum->name;
                $semester = $DB->get_field('local_curriculum_semesters',  'semester',  array('id'=>$data->semesterid));
                $dataobj->curriculum_semester = $semester;
                $courseid = $DB->get_field('local_cc_semester_courses',  'courseid',  array('id'=>$data->bclcid));
                $course = $DB->get_field('course',  'fullname',  array('id'=>$courseid));
                $dataobj->curriculum_course = $course;
                $dataobj->curriculum_startdate = date('d-m-Y',$curriculum->startdate);
                $dataobj->curriculum_enddate = date('d-m-Y',$curriculum->enddate);
                // $dataobj->curriculum_creater = $creater->firstname.' '.$creater->lastname;
                $dataobj->curriculum_session_name = $data->name;
                $adduser = $DB->get_record('user',array('id'=>$singleuser->id));
                $dataobj->curriculum_session_username = $adduser->firstname.' '.$adduser->lastname;

                $url = new moodle_url($CFG->wwwroot.'/local/program/sessions.php?bclcid='.$data->bclcid.'&semesterid='.$data->semesterid.'&ccid='.$data->curriculumid);
                $dataobj->curriculum_session_link = '<a href='.$url.'>'.$url.'</a>';
                $dataobj->curriculum_session_useremail = $adduser->email;
                $trainer = $DB->get_record('user',array('id'=>$data->trainerid));
                $dataobj->curriculum_session_trainername = fullname($trainer);
                $dataobj->curriculum_session_startdate = date('d-m-Y',$data->timestart);
                $dataobj->curriculum_session_enddate = date('d-m-Y',$data->timefinish);
                $completion_status = $DB->get_field('local_cc_session_signups', 'completion_status', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->semesterid, 'bclcid'=>$data->bclcid, 'sessionid'=>$data->id, 'userid'=>$singleuser->id));
                if($completion_status == SESSION_PRESENT) {
                    $dataobj->curriculum_session_attendance = 'Present';
                } else {
                    $dataobj->curriculum_session_attendance = 'Absent';
                }

                $completion_date=$DB->get_field('local_cc_session_signups', 'timemodified', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->semesterid, 'bclcid'=>$data->bclcid, 'sessionid'=>$data->id, 'userid'=>$singleuser->id));
                if(!empty($completion_date)){
                    $dataobj->curriculum_session_completiondate  =  date('d-m-Y',$completion_date);
                } else {
                    $dataobj->curriculum_session_completiondate  = 'NA';
                }

                $dataobj->adminbody = NULL;
                $dataobj->body = $get_notifications_emp->body;
                $touserid = $singleuser->id;
                $fromuserid = $USER->id;
                $notifications_lib = new notifications();
                $emailtype = $type;
                $dataobj->moduletype="curriculum_sessions";
                $dataobj->moduleid=$data->id;
                $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,0,0,0,$get_notifications_emp->id);
            }

            break;
        case 'program_cc_year_enrol':
        $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));

        $singleuser=new stdClass();
        $singleuser->id= $touserid ;
        $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser->id");
        $costcenter_trainer = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");

        // echo "SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj,moduleid)";

        $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj->programid,moduleid)");



        if(!$get_notifications_emp){
            $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and moduleid IS NULL");
        }
        if($get_notifications_emp){
            $from = $DB->get_record('user', array('id'=>$USER->id));
            $enrolleduser = $DB->get_record('user', array('id'=>$singleuser->id));
            $data=$DB->get_record('local_ccuser_year_signups',array('userid'=>$singleuser->id, 'yearid'=>$dataobj->yearid));
            $curriculum = $DB->get_record('local_curriculum',array('id'=>$data->curriculumid));
            $dataobj = new stdclass();
            $program = $DB->get_record('local_program',array('id'=>$data->programid));
            $org_program = $DB->get_field('local_costcenter', 'fullname', array('id'=>$costcenter->open_costcenterid));

            $creater = $DB->get_record('user',array('id'=>$data->usercreated));
            $dataobj->program_enroluserfulname = $enrolleduser->firstname.' '.$enrolleduser->lastname;
            $dataobj->program_enroluseremail = $enrolleduser->email;
            $dataobj->program_name = $program->fullname;
            $dataobj->program_code = $program->shortname;
            $dataobj->program_university = $org_program;
            $dataobj->program_curriculum_name = $curriculum->name;
            $year = $DB->get_field('local_program_cc_years',  'year',  array('id'=>$data->yearid));
            $dataobj->program_year_name = $year;
            $dataobj->program_creater = $creater->firstname.' '.$creater->lastname;

            // $semester = $DB->get_field('local_curriculum_semesters',  'semester',  array('id'=>$data->semesterid));
            // $dataobj->curriculum_semester = $semester;
            // $courseid = $DB->get_field('local_cc_semester_courses',  'courseid',  array('id'=>$data->bclcid));
            // $course = $DB->get_field('course',  'fullname',  array('id'=>$courseid));
            // $dataobj->curriculum_course = $course;
            // $dataobj->curriculum_startdate = date('d-m-Y',$curriculum->startdate);
            // $dataobj->curriculum_enddate = date('d-m-Y',$curriculum->enddate);
            // // $dataobj->curriculum_creater = $creater->firstname.' '.$creater->lastname;
            // $dataobj->curriculum_session_name = $data->name;
            $adduser = $DB->get_record('user',array('id'=>$singleuser->id));
            // $dataobj->curriculum_session_username = $adduser->firstname.' '.$adduser->lastname;

            $url = new moodle_url($CFG->wwwroot.'/local/program/view.php?ccid='.$data->curriculumid.'&prgid='.$data->programid);

            $dataobj->program_link = '<a href='.$url.'>'.$url.'</a>';
            $dataobj->curriculum_session_useremail = $adduser->email;
            $trainer = $DB->get_record('user',array('id'=>$data->trainerid));
            // $dataobj->curriculum_session_trainername = fullname($trainer);
            // $dataobj->curriculum_session_startdate = date('d-m-Y',$data->timestart);
            // $dataobj->curriculum_session_enddate = date('d-m-Y',$data->timefinish);
            // $completion_status = $DB->get_field('local_cc_session_signups', 'completion_status', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->semesterid, 'bclcid'=>$data->bclcid, 'sessionid'=>$data->id, 'userid'=>$singleuser->id));
            // if($completion_status == SESSION_PRESENT) {
            //     $dataobj->curriculum_session_attendance = 'Present';
            // } else {
            //     $dataobj->curriculum_session_attendance = 'Absent';
            // }

            // $completion_date=$DB->get_field('local_cc_session_signups', 'timemodified', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->semesterid, 'bclcid'=>$data->bclcid, 'sessionid'=>$data->id, 'userid'=>$singleuser->id));
            // if(!empty($completion_date)){
            //     $dataobj->curriculum_session_completiondate  =  date('d-m-Y',$completion_date);
            // } else {
            //     $dataobj->curriculum_session_completiondate  = 'NA';
            // }

            $dataobj->adminbody = NULL;
            $dataobj->body = $get_notifications_emp->body;
            $touserid = $singleuser->id;
            $fromuserid = $USER->id;
            $notifications_lib = new notifications();
            $emailtype = $type;
            $dataobj->moduletype="program";
            $dataobj->moduleid=$data->programid;
            $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,0,0,0,$get_notifications_emp->id);
        }

        break;

        case 'program_cc_year_faculty_enrol':
        $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));

        $singleuser=new stdClass();
        $singleuser->id= $touserid ;
        $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser->id");
        $costcenter_trainer = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");

        // echo "SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj,moduleid)";


        $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj->programid,moduleid)");


        if(!$get_notifications_emp){
            $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and moduleid IS NULL");
        }
        if($get_notifications_emp){
            $from = $DB->get_record('user', array('id'=>$USER->id));
            $data=$DB->get_record('local_cc_session_trainers',array('trainerid'=>$singleuser->id, 'courseid'=>$dataobj->courseid));
            $curriculum=$DB->get_record('local_curriculum',array('id'=>$data->curriculumid));
            $enrolleduser = $DB->get_record('user', array('id'=>$singleuser->id));

            $dataobj = new stdclass();
            $program = $DB->get_record('local_program',array('id'=>$data->programid));
            $org_program = $DB->get_field('local_costcenter', 'fullname', array('id'=>$costcenter->open_costcenterid));

            $creater = $DB->get_record('user',array('id'=>$data->usercreated));
            $dataobj->program_enroluserfulname = $enrolleduser->firstname.' '.$enrolleduser->lastname;
            $dataobj->program_enroluseremail = $enrolleduser->email;
            $dataobj->program_name = $program->fullname;
            $dataobj->program_code = $program->shortname;
            $dataobj->program_university = $org_program;
            $dataobj->program_curriculum_name = $curriculum->name;
            $year = $DB->get_field('local_program_cc_years',  'year',  array('id'=>$data->yearid));
            $dataobj->program_year_name = $year;
            $dataobj->program_creater = $creater->firstname.' '.$creater->lastname;

            $semester = $DB->get_field('local_curriculum_semesters',  'semester',  array('id'=>$data->semesterid));
            $dataobj->program_semester_name = $semester;
            // $courseid = $DB->get_field('local_cc_semester_courses',  'courseid',  array('id'=>$data->courseid));
            $course = $DB->get_field('course',  'fullname',  array('id'=>$data->courseid));
            $dataobj->program_course_name = $course;
            // $dataobj->curriculum_startdate = date('d-m-Y',$curriculum->startdate);
            // $dataobj->curriculum_enddate = date('d-m-Y',$curriculum->enddate);
            // $dataobj->curriculum_creater = $creater->firstname.' '.$creater->lastname;
            // $dataobj->curriculum_session_name = $data->name;
            // $adduser = $DB->get_record('user',array('id'=>$singleuser->id));
            // $dataobj->curriculum_session_username = $adduser->firstname.' '.$adduser->lastname;

            $url = new moodle_url($CFG->wwwroot.'/local/program/view.php?ccid='.$data->curriculumid.'&prgid='.$data->programid);

            $dataobj->program_link = '<a href='.$url.'>'.$url.'</a>';
            // $dataobj->curriculum_session_useremail = $adduser->email;
            $trainer = $DB->get_record('user',array('id'=>$data->trainerid));
            // $dataobj->curriculum_session_trainername = fullname($trainer);
            // $dataobj->curriculum_session_startdate = date('d-m-Y',$data->timestart);
            // $dataobj->curriculum_session_enddate = date('d-m-Y',$data->timefinish);
            // $completion_status = $DB->get_field('local_cc_session_signups', 'completion_status', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->semesterid, 'bclcid'=>$data->bclcid, 'sessionid'=>$data->id, 'userid'=>$singleuser->id));
            // if($completion_status == SESSION_PRESENT) {
            //     $dataobj->curriculum_session_attendance = 'Present';
            // } else {
            //     $dataobj->curriculum_session_attendance = 'Absent';
            // }

            // $completion_date=$DB->get_field('local_cc_session_signups', 'timemodified', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->semesterid, 'bclcid'=>$data->bclcid, 'sessionid'=>$data->id, 'userid'=>$singleuser->id));
            // if(!empty($completion_date)){
            //     $dataobj->curriculum_session_completiondate  =  date('d-m-Y',$completion_date);
            // } else {
            //     $dataobj->curriculum_session_completiondate  = 'NA';
            // }

            $dataobj->adminbody = NULL;
            $dataobj->body = $get_notifications_emp->body;
            $touserid = $singleuser->id;
            $fromuserid = $USER->id;
            $notifications_lib = new notifications();
            $emailtype = $type;
            $dataobj->moduletype="program";
            $dataobj->moduleid=$data->programid;
            $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,0,0,0,$get_notifications_emp->id);
        }

        break;

        case 'program_course_completion':
        $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));

        $singleuser=new stdClass();
        $singleuser->id= $touserid ;
        $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser->id");
        $costcenter_trainer = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");

        $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj->programid,moduleid)");


        if(!$get_notifications_emp){
            $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and moduleid IS NULL");
        }
        if($get_notifications_emp){
            $from = $DB->get_record('user', array('id'=>$USER->id));
            $data=$DB->get_record('local_cc_semester_courses',array('courseid'=>$dataobj->courseid));
            $enrolleduser = $DB->get_record('user', array('id'=>$singleuser->id));
            $curriculum=$DB->get_record('local_curriculum',array('id'=>$data->curriculumid));
            $program = $DB->get_record('local_program',array('id'=>$data->programid));
            $org_program = $DB->get_field('local_costcenter', 'fullname', array('id'=>$costcenter->open_costcenterid));

            $dataobj = new stdclass();

            $creater = $DB->get_record('user',array('id'=>$data->usercreated));
            $dataobj->program_enroluserfulname = $enrolleduser->firstname.' '.$enrolleduser->lastname;
            $dataobj->program_enroluseremail = $enrolleduser->email;
            $dataobj->program_name = $program->fullname;
            $dataobj->program_code = $program->shortname;
            $dataobj->program_university = $org_program;
            $dataobj->program_curriculum_name = $curriculum->name;

            $year = $DB->get_field('local_program_cc_years',  'year',  array('id'=>$data->yearid));
            $dataobj->program_year_name = $year;
            $semester = $DB->get_field('local_curriculum_semesters',  'semester',  array('id'=>$data->semesterid));
            $dataobj->program_semester_name = $semester;
            // $courseid = $DB->get_field('local_cc_semester_courses',  'courseid',  array('id'=>$data->bclcid));
            $course = $DB->get_field('course',  'fullname',  array('id'=>$data->courseid));
            $dataobj->program_course_name = $course;
            $course_completiondate = $DB->get_field('course_completions',  'timecompleted',  array('course'=>$data->courseid));
            $dataobj->program_year_course_completiondate = date('d-m-Y',$course_completiondate);
            // $dataobj->curriculum_enddate = date('d-m-Y',$curriculum->enddate);
            // $dataobj->curriculum_creater = $creater->firstname.' '.$creater->lastname;
            // $dataobj->curriculum_session_name = $data->name;
            // $adduser = $DB->get_record('user',array('id'=>$singleuser->id));
            // $dataobj->curriculum_session_username = $adduser->firstname.' '.$adduser->lastname;
            $dataobj->program_creater = $creater->firstname.' '.$creater->lastname;

            $url = new moodle_url($CFG->wwwroot.'/local/program/view.php?ccid='.$data->curriculumid.'&prgid='.$data->programid);
            $dataobj->program_link = '<a href='.$url.'>'.$url.'</a>';

            // $dataobj->curriculum_session_link = '<a href='.$url.'>'.$url.'</a>';
            // $dataobj->curriculum_session_useremail = $adduser->email;
            // $trainer = $DB->get_record('user',array('id'=>$data->trainerid));
            // $dataobj->curriculum_session_trainername = fullname($trainer);
            // $dataobj->curriculum_session_startdate = date('d-m-Y',$data->timestart);
            // $dataobj->curriculum_session_enddate = date('d-m-Y',$data->timefinish);
            // $completion_status = $DB->get_field('local_cc_session_signups', 'completion_status', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->semesterid, 'bclcid'=>$data->bclcid, 'sessionid'=>$data->id, 'userid'=>$singleuser->id));
            // if($completion_status == SESSION_PRESENT) {
            //     $dataobj->curriculum_session_attendance = 'Present';
            // } else {
            //     $dataobj->curriculum_session_attendance = 'Absent';
            // }

            // $completion_date=$DB->get_field('local_cc_session_signups', 'timemodified', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->semesterid, 'bclcid'=>$data->bclcid, 'sessionid'=>$data->id, 'userid'=>$singleuser->id));
            // if(!empty($completion_date)){
            //     $dataobj->curriculum_session_completiondate  =  date('d-m-Y',$completion_date);
            // } else {
            //     $dataobj->curriculum_session_completiondate  = 'NA';
            // }

            $dataobj->adminbody = NULL;
            $dataobj->body = $get_notifications_emp->body;
            $touserid = $singleuser->id;
            $fromuserid = $USER->id;
            $notifications_lib = new notifications();
            $emailtype = $type;
            $dataobj->moduletype="program";
            $dataobj->moduleid=$data->programid;
            $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,0,0,0,$get_notifications_emp->id);
        }

        break;
        case 'program_semester_completion':
        $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));

        $singleuser=new stdClass();
        $singleuser->id= $touserid ;
        $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser->id");
        $costcenter_trainer = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");

        // echo "SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj,moduleid)";


        $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj->programid,moduleid)");


        if(!$get_notifications_emp){
            $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and moduleid IS NULL");
        }
        if($get_notifications_emp){
            $from = $DB->get_record('user', array('id'=>$USER->id));
            $data=$DB->get_record('local_cc_semester_cmptl',array('semesterid'=>$dataobj->semesterid, 'userid'=>$singleuser->id));
            $enrolleduser = $DB->get_record('user', array('id'=>$singleuser->id));
            $curriculum=$DB->get_record('local_curriculum',array('id'=>$data->curriculumid));
            $program = $DB->get_record('local_program',array('id'=>$data->programid));
            $org_program = $DB->get_field('local_costcenter', 'fullname', array('id'=>$costcenter->open_costcenterid));
            $dataobj = new stdclass();

            $creater = $DB->get_record('user',array('id'=>$data->usercreated));
            $dataobj->program_enroluserfulname = $enrolleduser->firstname.' '.$enrolleduser->lastname;
            $dataobj->program_enroluseremail = $enrolleduser->email;
            $dataobj->program_name = $program->fullname;
            $dataobj->program_code = $program->shortname;
            $dataobj->program_university = $org_program;
            $dataobj->program_curriculum_name = $curriculum->name;

            $year = $DB->get_field('local_program_cc_years',  'year',  array('id'=>$data->yearid));
            $dataobj->program_year_name = $year;
            $semester = $DB->get_field('local_curriculum_semesters',  'semester',  array('id'=>$data->semesterid));
            $dataobj->program_semester_name = $semester;
            // $courseid = $DB->get_field('local_cc_semester_courses',  'courseid',  array('id'=>$data->bclcid));
            // $course = $DB->get_field('course',  'fullname',  array('id'=>$courseid));
            // $dataobj->curriculum_course = $course;
            // $dataobj->curriculum_startdate = date('d-m-Y',$curriculum->startdate);
            // $dataobj->curriculum_enddate = date('d-m-Y',$curriculum->enddate);
            // $dataobj->curriculum_creater = $creater->firstname.' '.$creater->lastname;
            // $dataobj->curriculum_session_name = $data->name;
            // $adduser = $DB->get_record('user',array('id'=>$singleuser->id));
            // $dataobj->curriculum_session_username = $adduser->firstname.' '.$adduser->lastname;

            $url = new moodle_url($CFG->wwwroot.'/local/program/view.php?ccid='.$data->curriculumid.'&prgid='.$data->programid);
            // $course_completiondate = $DB->get_field('course_completions',  'timecompleted',  array('course'=>$dataobj->courseid, 'userid'=>$dataobj->userid))
            $dataobj->program_semester_completiondate = date('d-m-Y',$data->completiondate);

            $dataobj->program_creater = $creater->firstname.' '.$creater->lastname;

            $url = new moodle_url($CFG->wwwroot.'/local/program/view.php?ccid='.$data->curriculumid.'&prgid='.$data->programid);
            $dataobj->program_link = '<a href='.$url.'>'.$url.'</a>';

            // $dataobj->curriculum_session_link = '<a href='.$url.'>'.$url.'</a>';
            // $dataobj->curriculum_session_useremail = $adduser->email;
            // $trainer = $DB->get_record('user',array('id'=>$data->trainerid));
            // // $dataobj->curriculum_session_trainername = fullname($trainer);
            // $dataobj->curriculum_session_startdate = date('d-m-Y',$data->timestart);
            // $dataobj->curriculum_session_enddate = date('d-m-Y',$data->timefinish);
            // $completion_status = $DB->get_field('local_cc_session_signups', 'completion_status', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->semesterid, 'bclcid'=>$data->bclcid, 'sessionid'=>$data->id, 'userid'=>$singleuser->id));
            // if($completion_status == SESSION_PRESENT) {
            //     $dataobj->curriculum_session_attendance = 'Present';
            // } else {
            //     $dataobj->curriculum_session_attendance = 'Absent';
            // }

            // $completion_date=$DB->get_field('local_cc_session_signups', 'timemodified', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->semesterid, 'bclcid'=>$data->bclcid, 'sessionid'=>$data->id, 'userid'=>$singleuser->id));
            // if(!empty($completion_date)){
            //     $dataobj->curriculum_session_completiondate  =  date('d-m-Y',$completion_date);
            // } else {
            //     $dataobj->curriculum_session_completiondate  = 'NA';
            // }

            $dataobj->adminbody = NULL;
            $dataobj->body = $get_notifications_emp->body;
            $touserid = $singleuser->id;
            $fromuserid = $USER->id;
            $notifications_lib = new notifications();
            $emailtype = $type;
            $dataobj->moduletype="program";
            $dataobj->moduleid=$data->programid;
            $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,0,0,0,$get_notifications_emp->id);
        }

        break;
        case 'program_year_completion':
        $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));

        $singleuser=new stdClass();
        $singleuser->id= $touserid ;
        $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser->id");
        $costcenter_trainer = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");

        // echo "SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj,moduleid)";


        $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj->programid,moduleid)");


        if(!$get_notifications_emp){
            $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and moduleid IS NULL");
        }
        if($get_notifications_emp){
            $from = $DB->get_record('user', array('id'=>$USER->id));
            $data=$DB->get_record('local_ccuser_year_signups',array('yearid'=>$dataobj->yearid, 'userid'=>$singleuser->id));
            $enrolleduser = $DB->get_record('user', array('id'=>$singleuser->id));
            $curriculum=$DB->get_record('local_curriculum',array('id'=>$data->curriculumid));

            $program = $DB->get_record('local_program',array('id'=>$data->programid));
            $org_program = $DB->get_field('local_costcenter', 'fullname', array('id'=>$costcenter->open_costcenterid));

            $dataobj = new stdclass();

            $creater = $DB->get_record('user',array('id'=>$data->usercreated));
            $dataobj->program_enroluserfulname = $enrolleduser->firstname.' '.$enrolleduser->lastname;
            $dataobj->program_enroluseremail = $enrolleduser->email;
            $dataobj->program_name = $program->fullname;
            $dataobj->program_code = $program->shortname;
            $dataobj->program_university = $org_program;
            $dataobj->program_curriculum_name = $curriculum->name;

            $year = $DB->get_field('local_program_cc_years',  'year',  array('id'=>$data->yearid));
            $dataobj->program_year_name = $year;
            // $semester = $DB->get_field('local_curriculum_semesters',  'semester',  array('id'=>$data->semesterid));
            // $dataobj->curriculum_semester = $semester;
            // $courseid = $DB->get_field('local_cc_semester_courses',  'courseid',  array('id'=>$data->bclcid));
            // $course = $DB->get_field('course',  'fullname',  array('id'=>$courseid));
            // $dataobj->curriculum_course = $course;
            // $dataobj->curriculum_startdate = date('d-m-Y',$curriculum->startdate);
            // $dataobj->curriculum_enddate = date('d-m-Y',$curriculum->enddate);
            // $dataobj->curriculum_creater = $creater->firstname.' '.$creater->lastname;
            // $dataobj->curriculum_session_name = $data->name;
            // $adduser = $DB->get_record('user',array('id'=>$singleuser->id));
            // $dataobj->curriculum_session_username = $adduser->firstname.' '.$adduser->lastname;

            $url = new moodle_url($CFG->wwwroot.'/local/program/view.php?ccid='.$data->curriculumid.'&prgid='.$data->programid);
            // $course_completiondate = $DB->get_field('course_completions',  'timecompleted',  array('course'=>$dataobj->courseid, 'userid'=>$dataobj->userid))
            $dataobj->program_year_completiondate = date('d-m-Y',$data->completiondate);


            $dataobj->program_creater = $creater->firstname.' '.$creater->lastname;

            $url = new moodle_url($CFG->wwwroot.'/local/program/view.php?ccid='.$data->curriculumid.'&prgid='.$data->programid);
            $dataobj->program_link = '<a href='.$url.'>'.$url.'</a>';

            // $dataobj->curriculum_session_link = '<a href='.$url.'>'.$url.'</a>';
            // $dataobj->curriculum_session_useremail = $adduser->email;
            // $trainer = $DB->get_record('user',array('id'=>$data->trainerid));
            // $dataobj->curriculum_session_trainername = fullname($trainer);
            // $dataobj->curriculum_session_startdate = date('d-m-Y',$data->timestart);
            // $dataobj->curriculum_session_enddate = date('d-m-Y',$data->timefinish);
            // $completion_status = $DB->get_field('local_cc_session_signups', 'completion_status', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->semesterid, 'bclcid'=>$data->bclcid, 'sessionid'=>$data->id, 'userid'=>$singleuser->id));
            // if($completion_status == SESSION_PRESENT) {
            //     $dataobj->curriculum_session_attendance = 'Present';
            // } else {
            //     $dataobj->curriculum_session_attendance = 'Absent';
            // }

            // $completion_date=$DB->get_field('local_cc_session_signups', 'timemodified', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->semesterid, 'bclcid'=>$data->bclcid, 'sessionid'=>$data->id, 'userid'=>$singleuser->id));
            // if(!empty($completion_date)){
            //     $dataobj->curriculum_session_completiondate  =  date('d-m-Y',$completion_date);
            // } else {
            //     $dataobj->curriculum_session_completiondate  = 'NA';
            // }

            $dataobj->adminbody = NULL;
            $dataobj->body = $get_notifications_emp->body;
            $touserid = $singleuser->id;
            $fromuserid = $USER->id;
            $notifications_lib = new notifications();
            $emailtype = $type;
            $dataobj->moduletype="program";
            $dataobj->moduleid=$data->programid;
            $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,0,0,0,$get_notifications_emp->id);
        }

        break;
        case 'program_completion':
        $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$type));

        $singleuser=new stdClass();
        $singleuser->id= $touserid ;
        $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $singleuser->id");
        $costcenter_trainer = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");

        // echo "SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj,moduleid)";


        $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and FIND_IN_SET($dataobj->programid,moduleid)");


        if(!$get_notifications_emp){
            $get_notifications_emp = $DB->get_record_sql("SELECT id,body,adminbody FROM {local_notification_info} WHERE notificationid = $get_notification_type->id and active=1 and costcenterid=$costcenter->open_costcenterid and moduleid IS NULL");
        }
        if($get_notifications_emp){
            $from = $DB->get_record('user', array('id'=>$USER->id));
            $enrolleduser = $DB->get_record('user', array('id'=>$singleuser->id));
            $data=$DB->get_record('local_curriculum_users',array('curriculumid'=>$dataobj->curriculumid, 'userid'=>$singleuser->id));
            $program = $DB->get_record('local_program',array('id'=>$data->programid));
            $org_program = $DB->get_field('local_costcenter', 'fullname', array('id'=>$costcenter->open_costcenterid));
            $curriculum=$DB->get_record('local_curriculum',array('id'=>$data->curriculumid));

            $dataobj = new stdclass();

            $creater = $DB->get_record('user',array('id'=>$data->usercreated));
            $dataobj->program_enroluserfulname = $enrolleduser->firstname.' '.$enrolleduser->lastname;
            $dataobj->program_enroluseremail = $enrolleduser->email;
            $dataobj->program_name = $program->fullname;
            $dataobj->program_code = $program->shortname;
            $dataobj->program_university = $org_program;
            $dataobj->program_curriculum_name = $curriculum->name;

            // $year = $DB->get_field('local_program_cc_years',  'year',  array('id'=>$data->yearid));
            // $dataobj->curriculum_year = $year;
            // $semester = $DB->get_field('local_curriculum_semesters',  'semester',  array('id'=>$data->semesterid));
            // $dataobj->curriculum_semester = $semester;
            // $courseid = $DB->get_field('local_cc_semester_courses',  'courseid',  array('id'=>$data->bclcid));
            // $course = $DB->get_field('course',  'fullname',  array('id'=>$courseid));
            // $dataobj->curriculum_course = $course;
            // $dataobj->curriculum_startdate = date('d-m-Y',$curriculum->startdate);
            // $dataobj->curriculum_enddate = date('d-m-Y',$curriculum->enddate);
            // // $dataobj->curriculum_creater = $creater->firstname.' '.$creater->lastname;
            // $dataobj->curriculum_session_name = $data->name;
            // $adduser = $DB->get_record('user',array('id'=>$singleuser->id));
            // $dataobj->curriculum_session_username = $adduser->firstname.' '.$adduser->lastname;

            // $url = new moodle_url($CFG->wwwroot.'/local/program/view.php?ccid='.$data->curriculumid);

            $dataobj->program_completiondate = date('d-m-Y',$data->completiondate);


            $dataobj->program_creater = $creater->firstname.' '.$creater->lastname;

            $url = new moodle_url($CFG->wwwroot.'/local/program/view.php?ccid='.$data->curriculumid.'&prgid='.$data->programid);
            $dataobj->program_link = '<a href='.$url.'>'.$url.'</a>';

            // $dataobj->curriculum_session_useremail = $adduser->email;
            // $trainer = $DB->get_record('user',array('id'=>$data->trainerid));
            // $dataobj->curriculum_session_trainername = fullname($trainer);
            // $dataobj->curriculum_session_startdate = date('d-m-Y',$data->timestart);
            // $dataobj->curriculum_session_enddate = date('d-m-Y',$data->timefinish);
            // $completion_status = $DB->get_field('local_cc_session_signups', 'completion_status', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->semesterid, 'bclcid'=>$data->bclcid, 'sessionid'=>$data->id, 'userid'=>$singleuser->id));
            // if($completion_status == SESSION_PRESENT) {
            //     $dataobj->curriculum_session_attendance = 'Present';
            // } else {
            //     $dataobj->curriculum_session_attendance = 'Absent';
            // }

            // $completion_date=$DB->get_field('local_cc_session_signups', 'timemodified', array('curriculumid'=>$data->curriculumid, 'semesterid'=>$data->semesterid, 'bclcid'=>$data->bclcid, 'sessionid'=>$data->id, 'userid'=>$singleuser->id));
            // if(!empty($completion_date)){
            //     $dataobj->curriculum_session_completiondate  =  date('d-m-Y',$completion_date);
            // } else {
            //     $dataobj->curriculum_session_completiondate  = 'NA';
            // }

            $dataobj->adminbody = NULL;
            $dataobj->body = $get_notifications_emp->body;
            $touserid = $singleuser->id;
            $fromuserid = $USER->id;
            $notifications_lib = new notifications();
            $emailtype = $type;
            $dataobj->moduletype="program";
            $dataobj->moduleid=$data->programid;
            $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,0,0,0,$get_notifications_emp->id);
        }

        break;
        }

    }
}
