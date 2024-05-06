<?php
use local_learningplan\lib\lib as lib;
require_once($CFG->dirroot.'/local/costcenter/lib.php');
require_once($CFG->dirroot.'/local/notifications/lib.php');
//require_once($CFG->dirroot.'/mod/facetoface/lib.php');

/**
 * class for notification trigger
 *
 * @package   local_notifications
 * @copyright 2018 Sreenivas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_triger {
    
    /* type of notiifcation*/
    private $type;
    
    /**
	* constructor for the notification trigger
	*
	* @param string $type type of notification
	*/
    function __construct($type){
        $this->type = $type;
        $this->costcenterobj = new costcenter();
    }
    /**
	* logs a record in email logs for a general course notification
	* @return void
	*/
    public function notification_for_new_course(){
        global $DB,$CFG, $USER;
        $type = "course_notification";
        /*Getting the notification type id*/
        $find_type_id = $DB->get_field('local_notification_type','id',array('shortname'=>$type));
        if(is_siteadmin()){
             $get_type_notifications = $DB->get_records('local_notification_info',array('notificationid'=>$find_type_id,'active'=>1));
             // print_object($get_type_notifications);
        }else{
            $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");
            $get_type_notifications = $DB->get_records('local_notification_info',array('notificationid'=>$find_type_id,'active'=>1,'costcenterid'=>$costcenter->open_costcenterid));
        }
        foreach($get_type_notifications as $get_notification){
         
            $day=$get_notification->reminderdays;
            $today=date('d-m-Y');
            $NewDate=Date('d-m-Y', strtotime("+".$day." days"));
            $notificatn_courses = $get_notification->moduleid;
            
            $sql = "select id,fullname,open_costcenterid from {course} where FROM_UNIXTIME(timecreated,'%Y-%m-%d') = CURDATE() and open_costcenterid=$get_notification->costcenterid ";
                
          $find_type_courses = $DB->get_field_sql("SELECT GROUP_CONCAT(moduleid) FROM {local_notification_info} WHERE id != $get_notification->id and notificationid=$find_type_id and moduleid IS NOT NULL");
               
          if(!empty($find_type_courses)){
               $find_type_courses=explode(',',$find_type_courses);
               
               $find_type_courses=implode(',',$find_type_courses);
               
               $sql .= " AND id not IN ($find_type_courses)";
          
          }
          if($notificatn_courses){
               $sql .= " AND id IN ($notificatn_courses)";
          }
           $get_records=$DB->get_records_sql($sql);
           foreach($get_records as $single){
                $sort = 'id';
                $field = 'id, userid';
                $sql = 'SELECT id,id as uid FROM {user} WHERE open_costcenterid = '.$single->open_costcenterid.' AND id > 2 AND deleted = 0 AND suspended = 0';
                $results = $DB->get_records_sql_menu($sql); 
                if($results){
                foreach($results as $singleuser){   
                        $from = $DB->get_record('user', array('id'=>$USER->id));
                        $data = $DB->get_record('course',array('id'=>$single->id));
                        $data_details = $DB->get_record('course',array('id'=>$single->id));
                        $department = $DB->get_field('local_costcenter','fullname',array('id'=>$data_details->open_departmentid));
                        $dataobj = new stdClass();
                        $dataobj->course_title = $data->fullname;
                        $dataobj->courseid = $single->id;
                        $dataobj->course_code = $data->shortname;                       
                        $dataobj->course_department = $department;
                        $dataobj->course_remainderdays = $get_notification->subject;
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
                        if($data->open_coursecompletiondays){
                            $dataobj->course_completiondays = $data->open_coursecompletiondays;
                        }else{
                            $dataobj->course_completiondays = 'N/A';   
                        }
                        
                        $dataobj->course_description = $data->summary;
                        $url = new moodle_url($CFG->wwwroot.'/course/view.php',array('id'=>$data->id));
                        $dataobj->course_link = html_writer::link($url, $data->fullname, array());
                        $dataobj->course_url = '<a href='.$url.'>'.$url.'</a>';
                        $course_imgurl = $this->costcenterobj->get_course_summary_file($data);
                        $dataobj->course_image = html_writer::img($course_imgurl, $data->fullname,array());
                        $adduser = $DB->get_record('user',array('id'=>$singleuser));
                        $dataobj->enroluser_username = $adduser->username;
                        $dataobj->enroluser_fullname = $adduser->firstname. ''. $adduser->lastname;
                        $dataobj->enroluser_email = $adduser->email;
                        if($data_details->open_coursecreator != 'NULL' && $data_details->open_coursecreator!=0){
                            $sql="select id, concat(firstname,' ', lastname) as fullname  from {user} where id = $data_details->open_coursecreator";   
                            $creator = $DB->get_record_sql($sql);
                            $dataobj->course_creator = $creator->fullname;
                        }else{
                            $dataobj->course_creator = "N/A";
                        }
                        $dataobj->adminbody = NULL;
                        $dataobj->body = $get_notification->body;
                        $touserid = $singleuser;
                        $fromuserid = $USER->id;
                        $notifications_lib = new notifications();
                        $emailtype = 'course_notification';
                        $dataobj->moduletype="course";
                        $dataobj->moduleid=$data->id;
                        $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,0,0,0,$get_notification->id);
                }
                 } 
            }
        }
    }
    
    
    /**
	* logs a record in email logs for a course completion notification
	* @return void
	*/
    public function course_completion_notification(){
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot.'/local/includes.php');
        $find_type_id=$DB->get_field('local_notification_type','id',array('shortname'=>$this->type));
       
        /****Query to get all the course with timecompled condition****/
        $sql = "select id, userid, course, timecompleted from {course_completions} where timecompleted IS NOT NULL AND DATE(FROM_UNIXTIME(timecompleted)) = DATE(NOW())";
          
        $get_completed_course=$DB->get_records_sql($sql);
        foreach($get_completed_course as $completed){
			$costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $completed->userid");
            $get_notifications_emp = $DB->get_record('local_notification_info', array('notificationid'=>$find_type_id,'active'=>1,'costcenterid'=>$costcenter->open_costcenterid),'id,body,adminbody');
            $user_completeddate =  date('d M Y', $completed->timecompleted);
            $timecompleted = strtotime($user_completeddate);
            $date = date("Y-m-d");
            $curentdate = strtotime($date); 
            /***to check time completed is equal to current date*****/
            if($timecompleted == $curentdate){
                /*Data objected created for sending Notification*/
                $userrecord = $DB->get_record('user', array('id'=>$completed->userid));
                $coursedata = $DB->get_record('course',array('id'=>$completed->course));
				if($coursedata->open_departmentid){
                    $department=$DB->get_field('local_costcenter','fullname',array('id'=>$coursedata->open_departmentid));
                }else{
                    $department='N/A';
                }
                $dataobj = new stdClass();
                $dataobj->course_title = $coursedata->fullname;
                if($coursedata->startdate){
                    $dataobj->course_enrolstartdate = date("d-m-Y", $coursedata->startdate);
                }else{
                    $dataobj->course_enrolstartdate = 'N/A';
                }
                if($coursedata->enddate){
                    $dataobj->course_enrolenddate = date("d-m-Y", $coursedata->enddate);
                }else{
                    $dataobj->course_enrolenddate = 'N/A';
                }
                if($completed->timecompleted) {
                    $dataobj->course_completiondate = $user_completeddate;
                }else {
                    $dataobj->course_completiondate = "N/A"; 
                }
             
                $dataobj->course_department = $department;
                
                if($coursedata->summary){
                    $dataobj->course_description=$coursedata->summary;
                }else{
                    $dataobj->course_description="N/A";
                }
                $url = new moodle_url($CFG->wwwroot.'/course/view.php',array('id'=>$coursedata->id));
                $includes = new user_course_details();
                $courseimage = $includes->course_summary_files($coursedata);
                $dataobj->course_link = html_writer::link($url, $coursedata->fullname, array());
                $dataobj->course_url = '<a href='.$url.'>'.$url.'</a>';
                // $course_imgurl = $this->costcenterobj->get_course_summary_file($coursedata);
                $dataobj->course_image = html_writer::img($courseimage, $coursedata->fullname,array());
                
                $dataobj->enroluser_username = $userrecord->username;
                $dataobj->enroluser_fullname = $userrecord->firstname. ''. $userrecord->lastname;;
                $dataobj->enroluser_email = $userrecord->email;
                $dataobj->courseid = $completed->course;
                $dataobj->adminbody = NULL;
                $dataobj->body = $get_notifications_emp->body;
                // if($coursedata->open_coursecreator != '') {
                //     $sql = "select id, concat(firstname,' ', lastname) as fullname  from {user} where id=$coursedata->open_coursecreator";
                //     $creator = $DB->get_record_sql($sql);
                //     $dataobj->course_creator = $creator->fullname;
                // }else{
                //     $dataobj->course_creator = "N/A";
                // }
                $touserid = $completed->userid;
                $fromuserid = 2;
               
                $notifications_lib = new notifications();
                $emailtype = $this->type;                
                $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid);
            }
        }
    }

    /**
    * logs a record in email logs for a learningplan completion notification
    * @return void
    */
    public function learningplan_completion_notification(){
        global $DB, $USER, $CFG;
        $get_notification_type = $DB->get_record('local_notification_type',array('shortname'=>$this->type));
        $sql = "select lla.id as lpuserid,llp.*,lla.userid from {local_learningplan} llp JOIN {local_learningplan_user} as lla on llp.id=lla.planid where lla.completiondate IS NOT NULL and lla.status=1 and llp.visible=1 AND DATE(FROM_UNIXTIME(lla.completiondate)) = DATE(NOW())";
        $completed_lep = $DB->get_records_sql($sql);
                // print_object($completed_lep);exit;
        foreach($completed_lep as $completed){
			$costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $completed->userid");
            $get_notifications_emp = $DB->get_record('local_notification_info', array('notificationid'=>$get_notification_type->id,'active'=>1,'costcenterid'=>$costcenter->open_costcenterid),'id,body,adminbody');
            $from = $DB->get_record('user', array('id'=>$USER->id));
            $data=$DB->get_record('local_learningplan',array('id'=>$completed->id));
            $courses = $DB->get_records_sql("select lcc.id,lcc.courseid,c.fullname as coursename
                        from {local_learningplan_courses} lcc 
                        JOIN {local_learningplan} lc ON lc.id = lcc.planid 
                        JOIN {course} c ON c.id = lcc.courseid
                        where lc.id = $data->id");
            $learningplan_lib = new lib();
            $users = $learningplan_lib->get_learningplan_assigned_users($data->id, array());
            if($courses){
                $val = array();
                foreach($courses as $course){
                    $val[] = $course->coursename;
                }
                $course_val = implode(' , ',$val).'.';
            }else{
                $course_val = 'N/A';
            }
            $dataobj = new stdclass();
            if($data->department){
                $dept=$DB->get_records_sql("SELECT fullname FROM {local_costcenter} WHERE id IN ($data->department)");
                $array = array();
                foreach($dept as $department){
                    $array[] = $department->fullname;
                }
                $dept_array = implode(' , ',$array).'.';
            }else{
                $dept_array='N/A';
            }
            $creater = $DB->get_record('user',array('id'=>$data->usercreated));
            $dataobj->lep_name = $data->name;
            $dataobj->lep_course = $course_val;
            if($data->learning_type == 1){
                $plan_type = 'Core Courses';
            }elseif($data->learning_type == 2){
                $plan_type = 'Elective Courses';
            }else{
                $plan_type = 'N/A';
            }
            $dataobj->lep_type = $plan_type;
            $dataobj->lep_creator = $creater->firstname.' '.$creater->lastname;
            $dataobj->lep_department = $dept_array;
            foreach ($users as $userstatus) {
                if($userstatus->status==1){
                    $completedstatus ="Completed";
                } 
                $dataobj->lep_completiondate = empty($userstatus->completiondate) ? 'N/A' : date('d M Y',$userstatus->completiondate);
                $dataobj->lep_status = empty($userstatus->status) ? 'Not Completed' : $completedstatus;
            }
            $adduser = $DB->get_record('user',array('id'=>$completed->userid));
            $dataobj->lep_enroluserfulname = $adduser->firstname.' '.$adduser->lastname;
            $url = new moodle_url($CFG->wwwroot.'/local/learningplan/plan_view.php?id='.$data->id);
            $dataobj->lep_link = '<a href='.$url.'>'.$url.'</a>';
            $dataobj->lep_enroluseremail = $adduser->email;
			 $dataobj->adminbody = NULL;
            $dataobj->body = $get_notifications_emp->body;
            $touserid = $completed->userid;
            $fromuserid = $USER->id;
            $notifications_lib = new notifications();
            $emailtype = $this->type; 
            $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid);
        }
    }
    
    public function enrol_reminder_schedule(){
        global $DB,$CFG,$USER;
        require_once($CFG->dirroot.'/local/includes.php');
        $type = "course_reminder";
        /*Getting the notification type id*/
        $find_type_id = $DB->get_field('local_notification_type','id',array('shortname'=>$type));
        $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id = $USER->id");
        /*Getting the notification record to find the users*/
        $get_type_notifications = $DB->get_records('local_notification_info',array('notificationid'=>$find_type_id,'active'=>1,'costcenterid'=>$costcenter->open_costcenterid));
        foreach($get_type_notifications as $get_type_notification){
            $day=$get_type_notification->reminderdays;
            $Today=date('d-m-Y');
            $NewDate=Date('d-m-Y', strtotime("+".$day." days"));
            //foreach($condition as $con){
            /*Getting the courses to find the users to whom notification should be sent*/
            $sql="SELECT e.id as enrolid,ue.*,c.id as courseid,c.fullname FROM {enrol} e JOIN {user_enrolments} ue ON e.id = ue.enrolid JOIN {course} c ON e.courseid = c.id WHERE FROM_UNIXTIME(c.enddate,'%Y-%m-%d') = DATE_ADD(CURDATE(),INTERVAL ".$day." 
                    DAY)"; 
            $get_course_id=$DB->get_records_sql($sql);
            /*Getting the all id from enrol to get the users from the user_enrolments*/
            foreach($get_course_id as $get){
                $courseid=$get->courseid;
                $sql="select userid,timecreated from {user_enrolments} where enrolid =".$get->enrolid."";        
                $get_users_id=$DB->get_records_sql($sql);
                /*Getting the users list to whom the notification should be sent*/
                foreach($get_users_id as $get_user){
                    $user_completedornot="select id from {course_completions} where userid=$get_user->userid and course = $courseid and timecompleted is NOT NULL";
                    $notcompleteduser=$DB->record_exists_sql($user_completedornot);
                    if(empty($notcompleteduser)){
                        if($get_user){
                             if($DB->record_exists('local_emaillogs',array('to_userid' => $get_user->userid, 'notification_infoid' => $get_type_notification->id,'from_userid'=>$USER->id))){
    
                             }else{ 
                                $singleuser = $get_user->userid;
                $from = $DB->get_record('user', array('id'=>$USER->id));
                $data=$DB->get_record('course',array('id'=>$courseid));
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
                if($day){
                    $dataobj->course_reminderdays = $day;
                }else{
                    $dataobj->course_reminderdays = 'N/A'; 
                }
                $dataobj->course_department = $department;
                $url = new moodle_url($CFG->wwwroot.'/course/view.php?id='.$data->id);
                if($data->summary){
                    $dataobj->course_description = $data->summary;
                }else{
                    $dataobj->course_description = 'N/A';
                }
                $dataobj->course_url = $url;
                if($data->open_coursecreator){
                    $dataobj->course_creator = $data->open_coursecreator;
                }else{
                    $dataobj->course_creator = 'N/A';
                }
                $dataobj->course_image = html_writer::img($courseimage, $data->fullname,array());
                $dataobj->enroluser_fullname = $adduser->firstname.' '.$adduser->lastname;
                $dataobj->enroluser_email = $adduser->email;
                $touserid = $singleuser;
                $fromuserid = $USER->id;
                // print_object($dataobj);exit;
                $notifications_lib = new notifications();
                $emailtype = $type;               
                $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid);
                             } 
                        }
                    }
                }                    
            }       
        }        
    }
    public function notification_for_ilt_reminder(){
        global $DB,$user;
        $type="ilt_reminder";/******This is ilt reminder notification*******/
        $get_ilt=$DB->get_record('local_notification_type',array('shortname'=>$type));
        $get_all_ilt=$DB->get_records('local_notification_info',array('notificationid'=>$get_ilt->id,'active'=>1));
        foreach($get_all_ilt as $single){ /**Gets the notification of reminder**/
            $day=$single->reminderdays;
            $id=$single->id;
            $sql="select * from {local_classroom} where id IN(".$single->courses.")";
            $get_single_batch =$DB->get_records_sql($sql); /**Gets all the Classroom where ids are present on the notification created**/
                   
            foreach($get_single_batch as $one){            
                $current_date = strtotime(date("d M Y"));
                $Finaldate = strtotime("+$day days", $current_date);/*Current date + Reminderdays*/
                if( $one->startdate == 0){
                    
                   
                }else{
                  $date = $one->startdate; /****startdate of the Classroom****/
                }
                $date_format= date('d M Y',$date);
                $startdate=strtotime($date_format);
        
                if($startdate == $Finaldate){                            
                    /***If condition Matches final and startdate****/
                    echo "condition";
                    $sort = 'id';
                    $fields = 'id, userid';
                    $result = $DB->get_records_menu('local_facetoface_users',array('f2fid'=>$one->id),$sort,$fields);
                    $users=implode(',',$result);
                    foreach($result as $singleuser){
                        $from = $DB->get_record('user', array('id'=>$USER->id));
                        $facetoface_course = $DB->get_field('local_facetoface_courses','courseid',array('batchid'=>$one->id));
                        $data=$DB->get_record('course',array('id'=>$facetoface_course));
                        //$data_details=$DB->get_record('local_coursedetails',array('courseid'=>$facetoface_course->courseid));
                        $department=$DB->get_field('local_costcenter','fullname',array('id'=>$one->costcenter));
                        $dataobj= new stdClass();
                        $dataobj->ilt_name = $one->name;
                        $dataobj->ilt_course = $data->fullname;
                        $ilt_prev_startdate = strtotime("-1 days", $one->startdate);/*Previous date*/
                        $dataobj->ilt_prev_startdate=date('d M Y',$ilt_prev_startdate);
                        $dataobj->ilt_startdate=date('d M Y',$one->startdate);
                        $dataobj->ilt_enddate=date('d M Y',$one->enddate);
                        $sql="select id, concat(firstname,' ', lastname) as fullname  from {user} where id=".$one->trainerid."";   
                        $creator=$DB->get_record_sql($sql);
                        $dataobj->ilt_creater=$creator->fullname;
                        //$dataobj->ilt_creater=$DB->get_field('user','firstname',array('id'=>$one->trainerid));
                        $dataobj->ilt_department=$department;
                        $dataobj->course_image= html_writer::img($course_imgurl, $data->fullname,array());
                        $dataobj->ilt_enroluser_username=$DB->get_field('user','username',array('id'=>$adduser->id));
                        $dataobj->ilt_enroluserfulname="[ilt_enroluserfulname]";
                        $dataobj->ilt_enroluseremail="[ilt_enroluseremail]";
                        $dataobj->ilt_sessionsinfo=ilt_sessions_for_emails($one->id);
                        
                        $url = new moodle_url($CFG->wwwroot.'/mod/facetoface/viewinfo.php',array('id'=>$one->id));
                        $user_url = html_writer::link($url, 'View Classroom', array());
                        $dataobj->ilt_link=$user_url;
                        /****** New string added by clients *****/
                        $institute=$DB->get_record('local_facetoface_institutes',array('id'=>$one->instituteid));
                        
                        $dataobj->ilt_location_fullname=$institute->fullname;
                        $dataobj->ilt_room_name=$DB->get_field('facetoface_room','name',array('id'=>$one->instituteid));
                        $dataobj->ilt_Addressclassroomlocation=$institute->address;
                        $dataobj->ilt_email_address_for_location=$institute->email;
                        $dataobj->ilt_phone_of_the_classroom_location=$institute->phnumber;
                        $dataobj->ilt_contact_name_details=$institute->contactdetails;
                        $dataobj->ilt_state=$institute->state;
                        
                        $dataobj->ilt_classroomsummarydescription=$one->intro;
                        $course_imgurl = get_ilt_attachment($one->id);
                        $dataobj->ilt_classroom_image= html_writer::img($course_imgurl, $data->name,array());
                        $touserid=$singleuser;
                        $fromuserid=2;
                        $notifications_lib = new notifications();
                        $emailtype='ilt_reminder';
                                                   
                        $notifications_lib->send_email_notification_ilt_reminder($emailtype, $dataobj, $touserid, $fromuserid,$id);
                        /***** created object and calling notification *****/
                    }
                }
            }
        }
    }

    public function notification_for_ilt_feedback(){
       global $DB,$USER,$CFG;
       /***********First Get The records with type called ilt_feedback;***************/
       $type="ilt_feedback";
       $get_feedback=$DB->get_field('local_notification_type','id',array('shortname'=>$type));
         
       /***********using that id get the ilt template records which has f2fid**********/
            $sql="select id,courses,reminderdays from {local_notification_info} where notificationid=$get_feedback and active=1";
            $get_notification=$DB->get_records_sql($sql);
            foreach($get_notification as $get_single){
                    /*********By Looping the we make single template *********/
                    $id=$get_single->id;
                    $sql="select * from {facetoface} where id IN(".$get_single->courses.")";
                    $get_single_batch =$DB->get_records_sql($sql);/*****The template belongings F2fid and getting F2F****/
                               
                    $day=$get_single->reminderdays;
                    foreach($get_single_batch as $one){
                                
                            /*************By Looping a template may contain more than one Facetoface ***********/
                            $sql="select id,f2fid,userid,trainerfeedback,trainingfeedback from {local_facetoface_users} where f2fid IN(".$one->id.")";
                            $get_feedback =$DB->get_records_sql($sql);
                         
                            /*************But the notification should sent according to reminderdays and looping for the there may me more users************/
                        foreach($get_feedback as $feed){
                        
                            if($feed->trainingfeedback!=''){
                                /*******For The trainingfeedback This is to make the completion time and add reminder days that is given in template when to run cron and current date*******/
                                $current_date = strtotime(date("d M Y"));
                                $date=$DB->get_field('facetoface','completion_time',array('id'=>$feed->f2fid));
                                $date_format= date('d M Y',$date);
                                
                                $startdate=strtotime($date_format);
                                $Finaldate = strtotime("+$day days", $startdate);
                                //print_object($Finaldate);
                               // print_object($current_date);
                                /*****If current and finaldate matches enters condition******/
                                if($current_date==$Finaldate){
                                    
                                    
                                    //$sort = 'id';
                                    //$fields = 'id, userid';
                                    //$result = $DB->get_records_menu('local_facetoface_users',array('f2fid'=>$feed->f2fid,'trainerfeedback'=>0),$sort,$fields);
                                    //$users=implode(',',$result);
                                  //  foreach($result as $singluser){
                                    
                                    $sql = 'SELECT id,id as idno FROM {local_evaluation} WHERE classid ='.$feed->f2fid;
                                    $feeds = $DB->get_records_sql_menu($sql);
                                    //print_object(count($feeds));//
                                    $eval=implode(',',$feeds);
                                    
                                    $sql="select id from {evaluation_completed} where userid=$feed->userid and evaluation IN($eval)";
                                    $check=$DB->get_records_sql_menu($sql);
                                    
                                    /*****The condition to check the count of sessions and count of given feedback for the session if doesnt matches enters and sends the mail******/
                                    if(count($feeds)!=count($check)){
                                        $courseid=$DB->get_field('local_facetoface_courses','courseid',array('batchid'=>$one->id));
                                        $from = $DB->get_record('user', array('id'=>$USER->id));
                                        $data=$DB->get_record('course',array('id'=>$courseid));
                                        $data_details=$DB->get_record('local_coursedetails',array('courseid'=>$courseid));
                                        $department=$DB->get_field('local_costcenter','fullname',array('id'=>$one->costcenter));
                                        
                                        // $coursenames=$DB->get_field('local_facetoface_courses','batchid',array('courseid'=>$courseid));
                                        
                                        $dataobj= new stdClass();
                                        $dataobj->ilt_name=$one->name;
                                        $dataobj->ilt_course=$data->fullname;
                                        $dataobj->ilt_startdate=date('d M Y',$one->startdate);
                                        $dataobj->ilt_enddate=date('d M Y',$one->enddate);
                                        $sql="select id, concat(firstname,' ', lastname) as fullname  from {user} where id=".$one->trainerid."";   
                                        $creator=$DB->get_record_sql($sql);
                                        $dataobj->ilt_creater=$creator->fullname;
                                        $dataobj->ilt_department=$department;
                                        $course_imgurl = get_course_summary_file($data);
                                        $dataobj->course_image= html_writer::img($course_imgurl, $data->fullname,array());
                                        $dataobj->ilt_enroluser_username=$DB->get_field('user','username',array('id'=>$feed->userid));
                                        $dataobj->ilt_enroluserfulname="[ilt_enroluserfulname]";
                                        $dataobj->ilt_enroluseremail="[ilt_enroluseremail]";
                                        $dataobj->ilt_sessionsinfo=ilt_sessions_for_emails($one->id);
                                        
                                        $url = new moodle_url($CFG->wwwroot.'/mod/facetoface/viewinfo.php',array('id'=>$feed->f2fid));
                                        $user_url = html_writer::link($url, 'View Classroom', array());
					                    $dataobj->ilt_link=$user_url;
                                        
                                        $touserid=$feed->userid;
                                        $fromuserid=2;
                                        $notifications_lib = new notifications();
                                        
                                        $emailtype='ilt_feedback';
                                        
                                        $notifications_lib->send_email_notification_ilt_reminder($emailtype, $dataobj, $touserid, $fromuserid,$id);
                                        //end of code
                                        }
                                   // }
                                }                  
                            }
                        } 
                    }
                }
           }
    public function notification_for_ilt_new_course(){
          global $DB,$user;
          $type="new_ilt_added";
          $get_notification_type=$DB->get_record('local_notification_type',array('shortname'=>$type));
          $get_notification=$DB->get_records('local_notification_info',array('notificationid'=>$get_notification_type->id,'active'=>1));
          $current_date = strtotime(date("d M Y"));
          $sql="select * from  {facetoface} as f2f where
          FROM_UNIXTIME(f2f.timemodified,'%Y-%m-%d')=(DATE_FORMAT(now(),'%Y-%m-%d'))";
          $get_all_ilt_course=$DB->get_records_sql($sql);
          foreach($get_all_ilt_course as $single){
                
              
                            $sort = 'id';
                            $field = 'id,userid';
                            $sql = 'SELECT id,userid FROM {local_userdata} WHERE costcenterid='.$single->costcenter.'';
                            
                            $result = $DB->get_records_sql_menu($sql);
                            if(count($result)>20){
                                                $to_userids=array_chunk($result, 20);
                                               
                                                 foreach($to_userids as $to_userid){
                                                     $users=implode(',',$to_userid);
                                                    
                                                $from = $DB->get_record('user', array('id'=>$USER->id));
                                                $data=$DB->get_record('course',array('id'=>$single->course));
                                                $data_details=$DB->get_record('local_coursedetails',array('courseid'=>$single->id));
                                                $department=$DB->get_field('local_costcenter','fullname',array('id'=>$data_details->costcenterid));
                                                $dataobj= new stdClass();
                                                $dataobj->ilt_name=$single->name;
                                                $dataobj->ilt_course=$data->shortname;
                                                $dataobj->ilt_startdate=date('d M Y',$single->startdate);
                                                $dataobj->ilt_enddate=date('d M Y',$single->enddate);
                                                $dataobj->ilt_creater=$single->trainerid;
                                                $dataobj->ilt_department=$department;
                                                $dataobj->course_image= html_writer::img($course_imgurl, $data->fullname,array());
                                                $dataobj->ilt_enroluser_username=$DB->get_field('user','username',array('id'=>$adduser->id));
                                                $dataobj->ilt_enroluserfulname=$adduser->firstname;
                                                $dataobj->ilt_enroluseremail=$adduser->email;
                                                $dataobj->ilt_sessionsinfo=ilt_sessions_for_emails($one->id);  
                                                $touserid=$users;
                                                $fromuserid=2;
                                                $notifications_lib = new notifications();
                                                $emailtype='new_course';
                                                                           
                                                $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid);
                                                 }
                                                }else{
                                                $users=implode(',',$result);
                                                    
                                                $from = $DB->get_record('user', array('id'=>$USER->id));
                                                $data=$DB->get_record('course',array('id'=>$single->id));
                                                $data_details=$DB->get_record('local_coursedetails',array('courseid'=>$single->id));
                                                $department=$DB->get_field('local_costcenter','fullname',array('id'=>$data_details->costcenterid));
                                                $dataobj= new stdClass();
                                                 $dataobj= new stdClass();
                                                $dataobj->ilt_name=$single->name;
                                                $dataobj->ilt_course=$data->shortname;
                                                $dataobj->ilt_startdate=date('d M Y',$single->startdate);
                                                $dataobj->ilt_enddate=date('d M Y',$single->enddate);
                                                $dataobj->ilt_creater=$single->trainerid;
                                                $dataobj->ilt_department=$department;
                                                $dataobj->course_image= html_writer::img($course_imgurl, $data->fullname,array());
                                                $dataobj->ilt_enroluser_username=$DB->get_field('user','username',array('id'=>$adduser->id));
                                                $dataobj->ilt_enroluserfulname=$adduser->firstname;
                                                $dataobj->ilt_enroluseremail=$adduser->email;
                                                $dataobj->ilt_sessionsinfo=ilt_sessions_for_emails($single->id);  
                                                $touserid=$users;
                                                $fromuserid=2;
                                                $notifications_lib = new notifications();
                                                $emailtype='new_course';
                                                                           
                                                $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid);
                                                }
                          
            }
         // }
     }
    public function get_caluclated($data){
        global $DB,$USER;
        $sql="select id,enrollenddate  from {local_coursedetails} where courseid IN(".$data->courses.") ";
        $get_course=$DB->get_records_sql($sql);
        return $get_course;
        }
    public function ilt_enrolnotification($batchid,$users){
        global $DB,$USER;        
        $ilt_course = $DB->get_field('local_facetoface_courses','courseid',array('batchid'=>$batchid));
        $coursename = $DB->get_field('course','fullname',array('id'=>$ilt_course));
        $batch_enrol_users = $DB->get_record('facetoface',array('id'=>$batchid));
		$data_details=$DB->get_field('facetoface','costcenter',array('id'=>$batchid));
		$department=$DB->get_field('local_costcenter','fullname',array('id'=>$data_details));
		if($department==''){
            $department="[ilt_department]";
        }
        $dataobj= new stdClass();
		$dataobj->ilt_name=$batch_enrol_users->name;
		$dataobj->ilt_course=$coursename;
		$dataobj->ilt_startdate=date('d M Y',$batch_enrol_users->startdate);
		$dataobj->ilt_enddate=date('d M Y',$batch_enrol_users->enddate);
        $sql="select id, concat(firstname,' ', lastname) as fullname  from {user} where id=$batch_enrol_users->trainerid";   
		$creator=$DB->get_record_sql($sql);
        $dataobj->ilt_creater=$creator->fullname;
		$dataobj->ilt_department=$department;
		$dataobj->course_image= html_writer::img($course_imgurl, $coursename,array());
		//$dataobj->ilt_enroluser_username=$DB->get_field('user','username',array('id'=>$adduser->id));
		$dataobj->ilt_enroluserfulname="[ilt_enroluserfulname]";
		$dataobj->ilt_enroluseremail="[ilt_enroluseremail]";
        //$dataobj->ilt_completiondate=date('d M Y',$facetoface->completion_time);
		$dataobj->ilt_sessionsinfo=ilt_sessions_for_emails($batchid);
        $url = new moodle_url($CFG->wwwroot.'/mod/facetoface/viewinfo.php',array('id'=>$batchid));
        $user_url = html_writer::link($url, 'View Classroom', array());
		/****** New string added by clients *****/
		 $institute=$DB->get_record('local_facetoface_institutes',array('id'=>$batch_enrol_users->instituteid));
				
        $dataobj->ilt_location_fullname=$institute->fullname;
        $dataobj->ilt_room_name=$DB->get_field('facetoface_room','name',array('id'=>$batch_enrol_users->instituteid));
        $dataobj->ilt_Addressclassroomlocation=$institute->address;
        $dataobj->ilt_email_address_for_location=$institute->email;
        $dataobj->ilt_phone_of_the_classroom_location=$institute->phnumber;
        $dataobj->ilt_contact_name_details=$institute->contactdetails;
        $dataobj->ilt_state=$institute->state;
        
        $dataobj->ilt_classroomsummarydescription=$batch_enrol_users->intro;
        $course_imgurl = get_ilt_attachment($batchid);
        $dataobj->ilt_classroom_image= html_writer::img($course_imgurl, $data->name,array());			
		$dataobj->ilt_link=$user_url;
        
        $notifications_lib = new notifications();
        foreach($users as $user){
        $emailtype="ilt_enrol";
        $touserid=$user;
        $fromuserid=2;
        $notifications_lib->send_email_notification($emailtype, $dataobj, $touserid, $fromuserid,$batchid,$planid=0);      
        } 
    }

    public function send_emaillog_notifications(){
        global $DB,$USER,$CFG;
        $sql = "select * from {local_emaillogs} where status = 0 AND FROM_UNIXTIME(time_created,'%Y-%m-%d') = CURDATE()";
        $logs = $DB->get_records_sql($sql);
        foreach($logs as $email_log){
            $record = new stdClass();
            $record->id = $email_log->id;
            $record->from_userid = $email_log->from_userid;
            $record->to_userid = $email_log->to_userid;
            $record->from_emailid = $email_log->from_emailid;
            $record->to_emailid = $email_log->to_emailid;
            $record->ccto = $email_log->ccto;
            $record->batchid = $email_log->batchid;
            $record->courseid = $email_log->courseid;
            $record->subject = $email_log->subject;
            $record->emailbody = $email_log->emailbody;
            $record->attachment_filepath = $email_log->attachment_filepath;
            $record->status = 1;
            $record->user_created = $email_log->user_created;
            $record->time_created = $email_log->time_created;
            $record->sent_date = $email_log->sent_date;
            $record->sent_by = $email_log->sent_by;
            $body = '';
            $DB->update_record('local_emaillogs',  $record);
            $touser = $DB->get_record('user', array('id'=>$record->to_userid));
            $from_user = $DB->get_record('user', array('id'=>$record->from_userid));

            $get_notification_infoid = $DB->get_field('local_notification_info','notificationid',array('id'=>$email_log->notification_infoid));
            $get_local_notification_type = $DB->get_field('local_notification_type','shortname',array('id'=>$get_notification_infoid));
        
            if($get_local_notification_type=='certification_complete'){
                $cert = $DB->record_exists('local_certification_users', array('userid' => $record->to_userid, 'certificationid' => $record->batchid,'completion_status'=>1));
                if($cert){
                    $tempdir = make_temp_directory('certificate/attachment');
                    if (!$tempdir) {
                        return false;
                    }

                    // Now, get the PDF.
                   // Create new customcert issue record if one does not already exist.
                    $img = new local_certification\certification();
                    if (!$DB->record_exists('local_certification_issues', array('userid' => $record->to_userid, 'certificationid' => $record->batchid))) {
                        $customcertissue = new stdClass();
                        $customcertissue->certificationid = $record->batchid;
                        $customcertissue->userid = $record->to_userid;
                      
                        $customcertissue->code =$img->generate_code();
                        $customcertissue->timecreated = time();
                        // Insert the record into the database.
                        $DB->insert_record('local_certification_issues', $customcertissue);
                    }
                  
                    $templateid = $DB->get_record_sql("SELECT id,name,templateid  FROM {local_certification} WHERE id = $record->batchid");
                    $template = $DB->get_record('local_certification_templts', array('id' => $templateid->templateid), '*', MUST_EXIST);
                    $template = new \local_certification\template($template);
                    $filecontents =$template->generate_pdf(false, $record->to_userid, true);
                    
                    // Set the name of the file we are going to send.
                    $filename = $templateid->name;
                    $filename = \core_text::entities_to_utf8($filename);
                    $filename = strip_tags($filename);
                    $filename = rtrim($filename, '.');
                    $filename = str_replace('&', '_', $filename) . '.pdf';
                
                    // Create the file we will be sending.
                    $tempfile = $tempdir . '/' . md5(microtime() . $record->to_userid) . '.pdf';
                    file_put_contents($tempfile, $filecontents);
                    email_to_user($touser, fullname($from_user), $record->subject, $body, $record->emailbody, $tempfile, $filename);
                }else{
                    email_to_user($touser, fullname($from_user), $record->subject, $body, $record->emailbody);
                }
            }else{
                email_to_user($touser, fullname($from_user), $record->subject, $body, $record->emailbody);
            }
        }
    }    
}  