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
 * local_notifications
 *
 * @package    local_notifications
 * @copyright  2018 sreenivas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Serve the new notification form as a fragment.
 *
 * @param array $args List of named arguments for the fragment loader.
 * @return string
 */
function local_notifications_output_fragment_new_notification_form($args) {
    global $CFG, $DB, $PAGE;
    $args = (object) $args;
    $context = $args->context;
    $id = $args->id;
    $o = '';

    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        parse_str($serialiseddata, $formdata);
    }
    $data = new stdclass();
    if ($id > 0) {
        $data = $DB->get_record('local_notification_info', array('id'=>$id));
        if($args->form_status == 0){
            if ($data->courses)
            $data->course = explode(',',$data->courses);
            $data->body =       array('text'=>$data->body, 'format'=>1);
        }else{
            $data->adminbody =       array('text'=>$data->adminbody, 'format'=>1);
        }

        $mform = new \local_notifications\forms\notification_form(null, array('form_status' => $args->form_status,'id' => $id), 'post', '', null, true, $formdata);
        $mform->set_data($data);
    }else{
    $params = array('form_status' => $args->form_status,'id' => $id);
    $mform = new \local_notifications\forms\notification_form(null, $params, 'post', '', null, true, $formdata);
    }

    if (!empty($args->jsonformdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }
    
    $formheaders = array_keys($mform->formstatus);
    $nextform = array_key_exists($args->form_status, $formheaders);
    if ($nextform === false) {
        return false;
    }
    $renderer = $PAGE->get_renderer('local_notifications');
    ob_start();
    $formstatus = array();
    foreach (array_values($mform->formstatus) as $k => $mformstatus) {
        $activeclass = $k == $args->form_status ? 'active' : '';
        $formstatus[] = array('name' => $mformstatus, 'activeclass' => $activeclass);
    }
    $formstatusview = new \local_notifications\output\form_status($formstatus);
    $o .= $renderer->render($formstatusview);
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();
 
    return $o;
}

class notifications {
	/**
	* notification strings
	*
	* @param string $notif_shortname notification identifier
	* @return string notification strings
	*/
    function get_string_identifiers($notif_shortname){
        
        switch($notif_shortname){
            
            case 'course_enrol':
                $strings = "[course_title], [course_enrolstartdate],
                            [course_enrolenddate], [course_department],
                            [course_description], [course_url],[course_image],
                            [enroluser_fullname], [enroluser_email]";
                break;
            case 'course_complete':
                $strings = "[course_title], [course_enrolstartdate],
                            [course_enrolenddate], [course_department],
                            [course_description], [course_url],[course_image],
                            [enroluser_fullname], [enroluser_email], [course_completiondate]";
                break;
            case 'course_unenroll':
                $strings = "[course_title], [course_enrolstartdate],
                            [course_enrolenddate], [course_department],
                             [course_description],[course_image],
                             [enroluser_fullname], [enroluser_email]";
                break;
            case 'course_reminder':
                $strings = "[course_title], [course_enrolstartdate],
                            [course_enrolenddate], [course_department],
                            [course_description],  [course_url],[course_image],
                            [enroluser_fullname], [enroluser_email], [course_reminderdays]";
                break;
			case 'course_notification':
                $strings = "[course_title], [course_enrolstartdate],
                            [course_enrolenddate], [course_department],
                            [course_description],  [course_url],[course_image],
                            [enroluser_fullname], [enroluser_email]";
                break;

            case 'program_cc_year_enrol':
                $strings = "[program_name], [program_code], [program_university], [program_curriculum_name],  [program_year_name], [program_enroluserfulname], [program_link], [program_enroluseremail], [program_creater]";   
                break;
            case 'program_cc_year_faculty_enrol':
                $strings = "[program_name], [program_code], [program_university], [program_curriculum_name],  [program_year_name], [program_semester_name], [program_course_name], [program_enroluserfulname], [program_link], [program_enroluseremail], [program_creater]";
                break;
            case 'program_course_completion':
                $strings = "[program_name], [program_code], [program_university], [program_curriculum_name], [program_year_name], [program_semester_name], [program_course_name], [program_enroluserfulname], [program_link], [program_enroluseremail], [program_year_course_completiondate], [program_creater]";
                break;
            case 'program_semester_completion':
                $strings = "[program_name], [program_code], [program_university], [program_curriculum_name], [program_year_name], [program_semester_name], [program_enroluserfulname], [program_link], [program_enroluseremail], [program_semester_completiondate], [program_creater]";   
                break;
            case 'program_year_completion':
                $strings = "[program_name], [program_code], [program_university], [program_curriculum_name], [program_year_name], [program_link], [program_enroluserfulname], [program_enroluseremail], [program_year_completiondate], [program_creater]";   
                break;
            case 'program_completion':
                $strings = "[program_name], [program_code], [program_university], [program_enroluserfulname], [program_enroluseremail], [program_completiondate], [program_creater]";   
                break;
        }
        return $strings;
    }

    
    
	/**
	* create / update notification template
	*
	* @param string $table
	* @param int $action insert / update value
	* @param object $dataobject object containing notiifcation info
	* @return boolean true / false based on db execution
	*/
	function insert_update_record($table, $action, $dataobject){
        global $DB;
        if($action == 'insert'){
            $systemcontext = context_system::instance();
            $str=$dataobject->body;
            $keywords = preg_split("/[\s,]+/", $dataobject->body);
            $keywords2 = preg_split("/[\s,]+/", $keywords[1]);
            $pieces = explode("/", $keywords2[0]);          
            file_save_draft_area_files($pieces[8], $systemcontext->id, 'local', 'notifications',$pieces[8], array('maxfiles' => 5));
            $result = $DB->insert_record("$table", $dataobject);
        } elseif($action == 'update') {
             $systemcontext = context_system::instance();
            $str=$dataobject->body;
            $keywords = preg_split("/[\s,]+/", $dataobject->body);
            $keywords2 = preg_split("/[\s,]+/", $keywords[1]);
            $pieces = explode("/", $keywords2[0]);  
            file_save_draft_area_files($pieces[8], $systemcontext->id, 'local', 'notifications',$pieces[8], array('maxfiles' => 5));
             $DB->update_record("$table", $dataobject);
             $result =$dataobject->id;
        }else{
            $result = false;
        }
        return $result;
    }
    
    
	/**
	* inserts notification info into emial log
	*
	* @param string $emailtype type of email
	
	* @param object $dataobj object containing notiifcation info
	* @param int $touserid recepient userid
	* @param int $fromuserid sender userid
	* @param int $batchid classroom id // optional
	* @param int $planid learning plan id // optional
	* @return boolean true / false based on db execution
	*/
	function send_email_notification($emailtype, $dataobj, $touserid, $fromuserid, $batchid = 0, $planid = 0) {
        global $DB, $USER; 
  
		if($touserid){
            $costcenter = $DB->get_record_sql("select open_costcenterid from {user} where id in($touserid)");			
		} else {
		    return false;
		}
		// if($batchid > 0){
		// 	$f2fid = $DB->get_field('local_classroom','costcenter',array('id'=>$batchid));
		// 	if($f2fid){				
		// 		$sql_content= "and ni.costcenterid = $costcenter->open_costcenterid" ; 
		// 	}
		// 	$sql = "SELECT ni.*
		// 	FROM {local_notification_info} ni
		// 	JOIN {local_notification_type} nt ON nt.id = ni.notificationid
		// 	WHERE nt.shortname = '$emailtype' $sql_content and ni.active = 1";
		// } elseif($planid > 0){
		// 	 $sql = "select id,costcenter as idd from {local_learningplan} where id=$planid";
		// 	 $costcenters = $DB->get_records_sql_menu($sql);
		  
		// 	if (in_array(1, $costcenters)){
		// 	   $costcenterid = 1;
		// 	   $sql_content = "and ni.costcenterid = $costcenterid " ;
		// 	}else{
		// 	   $sql_content = "and ni.costcenterid = $costcenter->open_costcenterid" ; 
		// 	}    
		// 	$sql = "SELECT ni.*
		// 	FROM {local_notification_info} ni
		// 	JOIN {local_notification_type} nt ON nt.id = ni.notificationid
		// 	WHERE nt.shortname = '$emailtype' $sql_content and ni.active=1";
		// } else {
			$sql = "SELECT ni.*
			FROM {local_notification_info} ni
			JOIN {local_notification_type} nt ON nt.id = ni.notificationid
			WHERE nt.shortname = '$emailtype' and ni.costcenterid = $costcenter->open_costcenterid and ni.active=1";
		// }
        $notfn_data = $DB->get_record_sql($sql);
            
        $touser = $DB->get_record('user', array('id'=>$touserid));
        $fromuser = $DB->get_record('user', array('id'=>$fromuserid));
      
        if($notfn_data){
            $dataobject = new stdclass();
            $dataobject->notification_infoid = $notfn_data->id;
            
            $dataobject->to_userid = $touserid;
            $dataobject->from_userid = $fromuserid;
            
            $subject = $this->replace_strings($dataobj, $notfn_data->subject);
             
            $dataobject->subject = $subject;

            // if($batchid>0){
            //     $dataobj->ilt_department = $DB->get_field('local_costcenter','fullname',array('id'=>$f2fid));
            // }elseif($planid>0){
            //     $dataobj->lep_department = $DB->get_field('local_costcenter','fullname',array('id'=>$costcenter->open_costcenterid));    
            //  }
             
            if($dataobj->body != NULL){
                $emailbody = $this->replace_strings($dataobj, $notfn_data->body);
            }else{
                $emailbody = $this->replace_strings($dataobj, $notfn_data->adminbody);
             }
               
            $dataobject->emailbody = $emailbody;
            
            if($notfn_data->attachment_filepath){
                $dataobject->attachment_filepath = $notfn_data->attachment_filepath;
            }
            if($notfn_data->enable_cc==1){
                $id = $DB->get_field('user','open_supervisorid',array('userid'=>$touserid));
                if($id){
                    $dataobject->ccto= $id;
                }else{
                  $dataobject->ccto= 0;  
                }
            } else {
                $dataobject->ccto=0;
            }
            
           
            $frommailid = $DB->get_field('user','email',array('id'=>$fromuserid));            
            $sql = "select id, email from {user} where id IN($touserid)";
            $tomailid = $DB->get_records_sql_menu($sql);
            $email = implode(',',$tomailid);
           
            $sentname = $DB->get_field('user','firstname',array('id'=>$USER->id));
            $dataobject->from_emailid = $frommailid;
            $dataobject->to_emailid = $email;
            $dataobject->sentdate = 0;
            $dataobject->sent_by = $USER->id;
            $dataobject->courseid = $dataobj->courseid;
            $dataobject->time_created = time();
            $dataobject->user_created = $USER->id;
            $dataobject->batchid = $batchid;
            $res =  "SELECT * FROM {local_emaillogs} WHERE to_userid = ".$touserid." AND notification_infoid =". $notfn_data->id ." AND from_userid = ".$fromuserid." AND subject = '$dataobject->subject' AND status = 0";

            //added by sarath for error reading databse
            if($dataobj->courseid){
                $res .= " AND courseid={$dataobj->courseid} "; 
            }//ended here by sarath

             $result_update = $DB->get_record_sql($res);
            // print_object($dataobject);
            // print_object($result_update);exit;
            if(empty($result_update)){
                $send=$DB->insert_record('local_emaillogs', $dataobject);
            }else{
                $status = new stdClass();
                $status->id= $result_update->id;
                $status->from_emailid = $frommailid;
                $status->to_emailid = $email;
                $status->sentdate = 0;
                $status->sent_by = $USER->id;
                $status->courseid = $dataobj->courseid;
                $status->time_created = time();
                $status->user_created = $USER->id;
                $status->batchid = $batchid;
                // $status->status=1;
                $send=$DB->update_record('local_emaillogs', $status);
            } 
        }
        return $dataobject->emailbody;
        
    }

    
	
	
	function send_email_notification_ilt_reminder($emailtype, $dataobj, $touserid, $fromuserid,$id){
       
        global $DB, $USER;
         
        $sql = "SELECT ni.*
                FROM {local_notification_info} ni
                JOIN {local_notification_type} nt ON nt.id = ni.notificationid
                WHERE nt.shortname = '$emailtype' and ni.id=$id and ni.active=1";
          
        $notfn_data = $DB->get_record_sql($sql);
        
        $touser = $DB->get_record('user', array('id'=>$touserid));
        $fromuser = $DB->get_record('user', array('id'=>$fromuserid));
       
        if($notfn_data){
            $dataobject = new stdclass();
            $dataobject->notification_infoid = $notfn_data->id;
            
            $dataobject->to_userid = $touserid;
            $dataobject->from_userid = $fromuserid;
            
            $subject = $this->replace_strings($dataobj, $notfn_data->subject);
             
            $dataobject->subject = $subject;
            
            $emailbody = $this->replace_strings($dataobj, $notfn_data->body);
          
            $dataobject->body_html = $emailbody;
            
            if($notfn_data->attachment_filepath){
                $dataobject->attachment_filepath = $notfn_data->attachment_filepath;
            }
            
            $dataobject->usercreated = $USER->id;
            $dataobject->time_created = time();
           
            $frommailid=$DB->get_field('user','email',array('id'=>$fromuserid));
            
            $sql="select id,email from {user} where id IN($touserid)";
            $tomailid=$DB->get_records_sql_menu($sql);
            $email=implode(',',$tomailid);
            if($notfn_data->enable_cc==1){
              $id=$DB->get_field('user','open_supervisorid',array('userid'=>$touserid));
                if($id){
                    $dataobject->ccto= $id;
                }else{
                  $dataobject->ccto= 0;  
                }
               
            }else{
                $dataobject->ccto=0;
            }
            
            $sentname=$DB->get_field('user','firstname',array('id'=>$USER->id));
            $dataobject->from_emailid=$frommailid;
            $dataobject->to_emailid=$email;
            $dataobject->sent_date=0;
            $dataobject->sentby_id=$USER->id;
            $dataobject->sentby_name=$sentname;
            $dataobject->courseid=$dataobj->courseid;
            $dataobject->created_date=time();
            $dataobject->batchid=0;
          
            $DB->insert_record('local_email_logs', $dataobject);
        }
        
        return $dataobject->emailbody;
        
    }

    
    
    function send_email_notification_course($emailtype, $dataobj, $touserid, $fromuserid,$course){
        global $DB, $USER;
		if($touserid){
			$sql = "select open_costcenterid from {user} where id in($touserid)";            
			$costcenter = $DB->get_record_sql($sql);
			//$costcenter=$DB->get_field('local_userdata','costcenterid',array('userid'=>$touserid));            
		}
		$course_costcenter = $DB->get_field('local_coursedetails','costcenterid',array('courseid'=>$course));
		//print_object($course_costcenter);
		if($course_costcenter){
			$check_acd = $DB->get_field('local_costcenter','shortname',array('id'=>$course_costcenter));
			//print_object($check_acd);
			if($check_acd == 'ACD'){
				$sql_content = "and ni.costcenterid = $course_costcenter " ;
			}else{
				 $sql_content = "and ni.costcenterid = $costcenter->costcenterid and ni.costcenterid = $course_costcenter " ; 
			   //$sql=" and ni.costcenterid=$costcenter->costcenterid ";
			}
		}
        $sql = "SELECT ni.*
                FROM {local_notification_info} ni
                JOIN {local_notification_type} nt ON nt.id = ni.notificationid
                WHERE nt.shortname = '$emailtype' $sql_content and ni.active=1";
             // echo $sql;exit;    
        $notfn_data = $DB->get_record_sql($sql);
       
        $touser = $DB->get_record('user', array('id'=>$touserid));
        $fromuser = $DB->get_record('user', array('id'=>$fromuserid));
       
        if($notfn_data){
            $dataobject = new stdclass();
            $dataobject->notification_infoid = $notfn_data->id;
            
            $dataobject->to_userid = $touserid;
            $dataobject->from_userid = $fromuserid;
            
            $subject = $this->replace_strings($dataobj, $notfn_data->subject);
             
            $dataobject->subject = $subject;
            
            $emailbody = $this->replace_strings($dataobj, $notfn_data->body);
          
            $dataobject->body_html = $emailbody;
            
            if($notfn_data->attachment_filepath){
                $dataobject->attachment_filepath = $notfn_data->attachment_filepath;
            }
            if($notfn_data->enable_cc==1){
                $id=$DB->get_field('user','open_supervisorid',array('userid'=>$touserid));
                if($id){
                    $dataobject->ccto= $id;
                }else{
                  $dataobject->ccto= 0;  
                }
               
            
            }else{
                $dataobject->ccto=0;
            }
            $dataobject->usercreated = $USER->id;
            $dataobject->time_created = time();
           
            $frommailid=$DB->get_field('user','email',array('id'=>$fromuserid));
            
            $sql="select id,email from {user} where id IN($touserid)";
            $tomailid=$DB->get_records_sql_menu($sql);
            $email=implode(',',$tomailid);
           
            $sentname=$DB->get_field('user','firstname',array('id'=>$USER->id));
            $dataobject->from_emailid=$frommailid;
            $dataobject->to_emailid=$email;
            $dataobject->sent_date=0;
            $dataobject->sentby_id=$USER->id;
            $dataobject->sentby_name=$sentname;
            $dataobject->courseid=$dataobj->courseid;
            $dataobject->created_date=time();
            $dataobject->batchid=0;
           
            $DB->insert_record('local_email_logs', $dataobject);
        }
        
        return $dataobject->emailbody;
        
    }
    
    function replace_strings($dataobject, $data){
		global $DB;
        
        $strings = $DB->get_records('local_notification_strings', array());        
        if($strings){
            foreach($strings as $string){
                foreach($dataobject as $key => $dataval){
                    $key = '['.$key.']';
                    if("$string->name" == "$key"){
                        $data = str_replace("$string->name", "$dataval", $data);
                    }
                }
            }
        }
        return $data;
    }

    function create_custom_email($formdata){
        global $DB, $USER;
        foreach($formdata->enrolledusers as $enroled_users){
            $dataobject = new stdClass();
            $dataobject->batchid = $formdata->ilt;
            $dataobject->from_emailid = $USER->email;
            $dataobject->from_userid = $USER->id;
            $to_emaiid = $DB->get_field('user','email',array('id'=>$enroled_users));
            $dataobject->to_emailid = $to_emaiid;
            $dataobject->to_userid = $enroled_users;
            $supevisor_id = $DB->get_field('user','open_supervisorid',array('userid'=>$enroled_users));
            if(!empty($formdata->enable_cc) && ($supevisor_id)){
                $dataobject->ccto= $supevisor_id;
            }else{
                $dataobject->ccto= 0;  
            }
            $dataobject->subject = $formdata->subject;
            $dataobject->body_html = $formdata->body['text'];
            $dataobject->created_date = time();
            $dataobject->courseid = -1;
            $dataobject->time_created = time();
            $insertdata = $DB->insert_record('local_email_logs', $dataobject);
        }
        return $insertdata;
        
    }
    
    function update_custom_email($formdata){
        global $DB, $USER;
        //updated by rajesh mythri
        foreach($formdata->enrolledusers as $enroled_users){
            $dataobject = new stdClass();
            $dataobject->batchid = $formdata->ilt;
            $dataobject->from_emailid = $USER->email;
            $dataobject->from_userid = $USER->id;
            $to_emaiid = $DB->get_field('user','email',array('id'=>$enroled_users));
            $dataobject->to_emailid = $to_emaiid;
            $dataobject->to_userid = $enroled_users;
            $supevisor_id = $DB->get_field('user','open_supervisorid',array('userid'=>$enroled_users));
            if(!empty($formdata->enable_cc) && ($supevisor_id)){
                $dataobject->ccto= $supevisor_id;
            }else{
                $dataobject->ccto= 0;  
            }
            $dataobject->subject = $formdata->subject;
            $dataobject->body_html = $formdata->body['text'];
            $dataobject->created_date = time();
            $dataobject->courseid = -1;
            $dataobject->time_created = time();
            $updatedata = $DB->update_record('local_email_logs', $dataobject);
        }
        return $updatedata;
    }
    
    function delete_custom_email($id){
        global $DB;        
        $result = $DB->delete_records("local_email_logs", array('id'=>$id));        
        return $result;
    }
	function get_course_completiondays($costcenterid){
        global $DB;        
		$sql = "SELECT coursecompletiondays, coursecompletiondays as coursecompletiondays_val
                FROM {local_coursedetails} cd where cd.costcenterid=$costcenterid  GROUP BY coursecompletiondays";                
        $completiondays = $DB->get_records_sql_menu($sql);        
        $course_completiondays = array(null => get_string('select_opt', 'local_notifications')) + $completiondays;        
        return $course_completiondays;
    }
}
/*
* Author Rizwana
* Displays a node in left side menu
* @return  [type] string  link for the leftmenu
*/
function local_notifications_leftmenunode(){
    $systemcontext = context_system::instance();
    $notificationsnode = '';
    // if(has_capability('local/notifications:view',$systemcontext) || is_siteadmin()){
    if(is_siteadmin() || has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
        $notificationsnode .= html_writer::start_tag('li', array('id'=> 'id_leftmenu_notifications', 'class'=>'pull-left user_nav_div notifications dropdown-item'));
            $notifications_url = new moodle_url('/local/notifications/index.php');
            $notifications = html_writer::link($notifications_url, '<i class="fa fa-bell-o"></i><span class="user_navigation_link_text">'.get_string('pluginname','local_notifications').'</span>',array('class'=>'user_navigation_link'));
            $notificationsnode .= $notifications;
        $notificationsnode .= html_writer::end_tag('li');
    }

    return array('16' => $notificationsnode);
}