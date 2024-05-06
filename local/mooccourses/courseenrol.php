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
 * Manual user enrolment UI.
 *
 * @package    enrol_manual
 * @copyright  2017 sreekanth
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
ini_set('memory_limit', '-1');
define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once($CFG->dirroot.'/local/mooccourses/lib.php');
// require_once($CFG->dirroot.'/local/courses/filters_form.php');
require_once($CFG->dirroot.'/local/mooccourses/moocfilters_form.php');
require_once($CFG->dirroot.'/local/lib.php');
// require_once($CFG->dirroot.'/local/courses/lib.php');
// require_once($CFG->dirroot.'/local/courses/notifications_emails.php');
use \local_courses\notificationemails as coursenotifications_emails;
use \local_assignroles\local\assignrole as assignrole; 
global $CFG,$DB,$USER,$PAGE,$OUTPUT,$SESSION;

$view=optional_param('view','page', PARAM_RAW);
$type=optional_param('type','', PARAM_RAW);
$lastitem=optional_param('lastitem',0, PARAM_INT);

$enrolid = required_param('enrolid', PARAM_INT);
$course_id = optional_param('id', 0,PARAM_INT);
$organization = optional_param('costcenter', 0,PARAM_INT);
$departmentid = optional_param('department',0,PARAM_INT);
$roleid = optional_param('roleid', -1, PARAM_INT);
$instance = $DB->get_record('enrol', array('id'=>$enrolid, 'enrol'=>'manual'), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$instance->courseid), '*', MUST_EXIST);
$submit_value = optional_param('submit_value','', PARAM_RAW);
$add = optional_param('add',array(), PARAM_RAW);
$remove=optional_param('remove',array(), PARAM_RAW);
$sesskey=sesskey();
$context = context_course::instance($course->id, MUST_EXIST);
require_login();

if($view == 'ajax'){
  $options =(array)json_decode($_GET["options"],false);
  $select_from_users=mooccourse_enrolled_users($type, $course_id,$options,false,$offset1=-1,$perpage=50,$lastitem);
  echo json_encode($select_from_users);
  exit;
}

$canenrol = has_capability('local/costcenter_course:enrol', $context);
//$canunenrol = has_capability('local/costcenter_course:unenrol', $context);
// Note: manage capability not used here because it is used for editing
// of existing enrolments which is not possible here.
if (!$canenrol) {
    // No need to invent new error strings here...
    require_capability('local/costcenter_course:enrol', $context);
    require_capability('enrol/manual:unenrol', $context);
}
/*Department level restrictions */
require_once($CFG->dirroot.'/local/includes.php');
$userlist=new has_user_permission();
$haveaccess=$userlist->access_courses_permission($course_id);
if(!$haveaccess) {
	 redirect($CFG->wwwroot . '/local/courses/error.php?id=2');
}
if ($roleid < 0) {
    $roleid = $instance->roleid;
}
// < Mallikarjun ODL-850 Displaying all role users --starts>
$facultyroleid = $DB->get_field('role', 'id', array('shortname' => 'faculty'));
if($roleid != $facultyroleid){
  $roles = (new assignrole)->get_assignable_roles($context);
  $roles = array('0'=>get_string('none')) + $roles;

  if (!isset($roles[$roleid])) {
      //Weird - security always first!
      $roleid = 0;
  }
}
// < Mallikarjun ODL-850 Displaying all role users --ends>
if (!$enrol_manual = enrol_get_plugin('manual')) {
    throw new coding_exception('Can not instantiate enrol_manual');
}

$instancename = $enrol_manual->get_instance_name($instance);

$PAGE->set_url('/local/mooccourses/courseenrol.php', array('id'=>$course_id,'enrolid'=>$instance->id,'costcenter'=>$organization, 'roleid'=>$roleid));
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(get_string('manage_courses','local_courses'),new moodle_url('/local/mooccourses/index.php'));
$PAGE->navbar->add(get_string('userenrolments', 'local_courses'));
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->js('/local/courses/js/jquery.bootstrap-duallistbox.js',true);
$PAGE->requires->css('/local/courses/css/bootstrap-duallistbox.css');
$PAGE->set_title($enrol_manual->get_instance_name($instance));
if($roleid){
    $rolename = $DB->get_field('role', 'shortname', array('id' => $roleid));
}
if(!$add&&!$remove){
  if($rolename == 'student'){ 
      // $PAGE->set_heading('Enroll Students to "'.$course->fullname.'" Course');
      $PAGE->set_heading(get_string('studentmooccourseenrollments', 'local_mooccourses', $course->fullname));
  }elseif($rolename == 'faculty'){
      $PAGE->set_heading(get_string('facultymooccourseenrollments', 'local_mooccourses', $course->fullname));
  }else{
      $PAGE->set_heading($course->fullname);
  }
}
navigation_node::override_active_url(new moodle_url('/local/mass_enroll/mass_enroll.php', array('id'=>$course->id)));
$systemcontext = context_system::instance();
//Create the user selector objects.
//$options = array('enrolid' => $enrolid, 'accesscontext' => $context);
 
if(is_siteadmin()){
   $costcenter="";
}else{
     $costcenter=$DB->get_field('course','open_costcenterid',array('id'=>$course_id));
}
    // print_object($organization);exit;

echo $OUTPUT->header();
if ($course) {
  $department   = null;
  $email        = null;
  $idnumber     = null;
  $uname        = null;
  $groups       = null;
  $filterlist = get_mooccoursesfilterslist();
  $mform = new moocfilters_form($PAGE->url, array('filterlist'=>$filterlist,'enrolid'=>$enrolid, 'courseid'=>$course_id, 'costcenter' => $organization, 'action' => 'mooccourse_enrolments', 'roleid' => $roleid));
  if ($mform->is_cancelled()) {
    redirect($PAGE->url);
  } else {
    $filterdata =  $mform->get_data();
    if($filterdata){
        $collapse = false;
    } else{
        $collapse = true;
    }
    // $organization = !empty($filterdata->organizations) ? implode(',', $filterdata->organizations) : null;
    $department = !empty($filterdata->departments) ? implode(',', $filterdata->departments) : null;
    $email = !empty($filterdata->email) ? implode(',', $filterdata->email) : null;
    $idnumber = !empty($filterdata->idnumber) ? implode(',', $filterdata->idnumber) : null;
    $uname = !empty($filterdata->users) ? implode(',', $filterdata->users) : null;
    $groups = !empty($filterdata->groups) ? implode(',', $filterdata->groups) : null;
  }

    // Create the user selector objects.
// <sandeep> - SRIVSPT-3 Manage Mooc Courses Module Changes -- Starts
  if($course->open_departmentid == 0){
    $options = array('context' => $context->id, 'courseid' => $course_id, 'organization' => $organization, 'department' => $department, 'email' => $email, 'idnumber' => $idnumber, 'uname' => $uname, 'roleid' => $roleid, 'groups' => $groups);    
  } else{
    $options = array('context' => $context->id, 'courseid' => $course_id, 'organization' => $organization, 'department' => $department, 'email' => $email, 'idnumber' => $idnumber, 'uname' => $uname, 'roleid' => $roleid, 'groups' => $groups);
  }
// <sandeep> - SRIVSPT-3 Manage Mooc Courses Module Changes -- Ends
	$dataobj = $course_id;
	$fromuserid = $USER->id;
    if ( $add AND confirm_sesskey()) {
		$type = 'course_enrol';
        if($submit_value == "Add_All_Users"){
					$options =json_decode($_REQUEST["options"],false);
              $userstoassign=array_flip(mooccourse_enrolled_users('add', $course_id, (array)$options, false, $offset1=-1, $perpage=-1));
        }else{
            $userstoassign =$add;
        }
        if (!empty($userstoassign)) {
						$progress = 0;
						$progressbar = new \core\progress\display_if_slow(get_string('enrollusers', 'local_mooccourses',$course->fullname));
						$progressbar->start_html();
						$progressbar->start_progress('',count($userstoassign)-1);
						foreach($userstoassign as $key=>$adduser){
							$progressbar->progress($progress);
							$progress++;
							$timestart = $course->startdate;
							$timeend = 0;
							if($timestart==''){
								$timestart=0;
							}
							$enrol_manual->enrol_user($instance, $adduser, $roleid, $timestart, $timeend);
              $emaillogs = new coursenotifications_emails();
              $email_logs = $emaillogs->course_emaillogs($type,$dataobj,$adduser,$fromuserid);
						}
						$progressbar->end_html();
            $result=new stdClass();
            $result->changecount=$progress;
            $result->course=$course->fullname; 

            if($rolename == 'student'){
                $enrollsuccessmsg = get_string('enrolluserssuccess', 'local_mooccourses',$result);
            }else{
                $enrollsuccessmsg = get_string('enrollfacultysuccess', 'local_mooccourses',$result);
            }
            echo $OUTPUT->notification($enrollsuccessmsg,'success');
            $button = new single_button($PAGE->url, get_string('click_continue','local_courses'), 'get', true);
            $button->class = 'continuebutton';
            echo $OUTPUT->render($button);
            die();
        }
    }
    if ( $remove&& confirm_sesskey()) {
        $type = 'course_unenroll';
        if($submit_value=="Remove_All_Users"){
					$options =json_decode($_REQUEST["options"],false);
             $userstounassign = array_flip(mooccourse_enrolled_users('remove',$course_id,(array)$options,false,$offset1=-1,$perpage=-1));
        }else{
            $userstounassign = $remove;
        }
        if (!empty($userstounassign)) {
						$progress = 0;
						$progressbar = new \core\progress\display_if_slow(get_string('un_enrollusers', 'local_mooccourses',$course->fullname));
						$progressbar->start_html();
						$progressbar->start_progress('', count($userstounassign)-1);
						foreach($userstounassign as $key=>$removeuser){
								$progressbar->progress($progress);
								$progress++;
								if($instance->enrol=='manual'){
									$manual=$enrol_manual->unenrol_user($instance, $removeuser);
                  $emaillogs = new coursenotifications_emails();
                  $email_logs = $emaillogs->course_emaillogs($type,$dataobj,$removeuser,$fromuserid);
								}
								$data_self=$DB->get_record_sql("Select * from {user_enrolments} ue join {enrol} e  where ue.enrolid=e.id and e.courseid=$course_id and ue.userid='".$removeuser."'");
								$enrol_self = enrol_get_plugin('self');
								if($data_self->enrol=='self'){
									$self=$enrol_self->unenrol_user($data_self, $removeuser);
                  $emaillogs = new coursenotifications_emails();
                  $email_logs = $emaillogs->course_emaillogs($type,$dataobj,$removeuser,$fromuserid);
								}
						}
						$progressbar->end_html();
						$result=new stdClass();
						$result->changecount=$progress;
						$result->course=$course->fullname; 
						
						if($rolename == 'student'){
                $unenrollsuccessmsg = get_string('unenrolluserssuccess', 'local_mooccourses',$result);
            }else{
                $unenrollsuccessmsg = get_string('unenrollfacultysuccess', 'local_mooccourses',$result);
            }
            echo $OUTPUT->notification($unenrollsuccessmsg,'success');
						$button = new single_button($PAGE->url, get_string('click_continue','local_courses'), 'get', true);
						$button->class = 'continuebutton';
						echo $OUTPUT->render($button);
						die();
        }
    }
   
    $select_to_users = mooccourse_enrolled_users('add', $course_id, $options, false, $offset=-1, $perpage=50);
    $select_to_users_total = mooccourse_enrolled_users('add', $course_id, $options, true, $offset1=-1, $perpage=-1);
 
    $select_from_users = mooccourse_enrolled_users('remove', $course_id, $options, false, $offset1=-1, $perpage=50);
    $select_from_users_total = mooccourse_enrolled_users('remove', $course_id, $options, true, $offset1=-1, $perpage=-1);

    $select_all_enrolled_users='&nbsp&nbsp<button type="button" id="select_add" name="select_all" value="Select All" title="Select All"/ class="btn btn-default">'.get_string('select_all', 'local_courses').'</button>';
    $select_all_enrolled_users.='&nbsp&nbsp<button type="button" id="add_select" name="remove_all" value="Remove All" title="Remove All" class="btn btn-default"/>'.get_string('remove_all', 'local_courses').'</button>';
    
    $select_all_not_enrolled_users='&nbsp&nbsp<button type="button" id="select_remove" name="select_all" value="Select All" title="Select All" class="btn btn-default"/>'.get_string('select_all', 'local_courses').'</button>';
    $select_all_not_enrolled_users.='&nbsp&nbsp<button type="button" id="remove_select" name="remove_all" value="Remove All" title="Remove All" class="btn btn-default"/>'.get_string('remove_all', 'local_courses').'</button>';
    
    
   $content='<div class="bootstrap-duallistbox-container">';
	 $encoded_options = json_encode($options);
   $content.='<form  method="post" name="form_name" id="user_assign" class="form_class" ><div class="box2 col-md-5">
    <input type="hidden" name="id" value="'.$course_id.'"/>
		<input type="hidden" name="enrolid" value="'.$enrolid.'"/>
    <input type="hidden" name="costcenter" value="'.$organization.'"/>
    <input type="hidden" name="sesskey" value="'.sesskey().'"/>
		<input type="hidden" name="options"  value='.$encoded_options.' />
   <label>'.get_string('enrolled_users', 'local_courses',$select_from_users_total).$select_all_not_enrolled_users.'</label>';
   $content.='<select multiple="multiple" name="remove[]" id="bootstrap-duallistbox-selected-list_duallistbox_courses_users" class="dual_select">';
   foreach($select_from_users as $key=>$select_from_user){
          $content.="<option value='$key'>$select_from_user</option>";
    }

   $content.='</select>';
   $content.='</div><div class="box3 col-md-2 actions"><button type="submit" class="custom_btn btn remove btn-default" disabled="disabled" title="Remove Selected Users" name="submit_value" value="Remove Selected Users" id="user_unassign_all"/>
       '.get_string('remove_selected_users', 'local_courses').'
        </button></form>';
   $content.='<form  method="post" name="form_name" id="user_un_assign" class="form_class" ><button type="submit" class="custom_btn btn move btn-default" disabled="disabled" title="Add Selected Users" name="submit_value" value="Add Selected Users" id="user_assign_all" />
       '.get_string('add_selected_users', 'local_courses').'
        </button></div><div class="box1 col-md-5">
    <input type="hidden" name="id" value="'.$course_id.'"/>
    <input type="hidden" name="enrolid" value="'.$enrolid.'"/>
    <input type="hidden" name="costcenter" value="'.$organization.'"/>
    <input type="hidden" name="sesskey" value="'.sesskey().'"/>
		<input type="hidden" name="options"  value='.$encoded_options.' />
   <label> '.get_string('availablelist', 'local_courses',$select_to_users_total).$select_all_enrolled_users.'</label>';
    $content.='<select multiple="multiple" name="add[]" id="bootstrap-duallistbox-nonselected-list_duallistbox_courses_users" class="dual_select">';
    foreach($select_to_users as $key=>$select_to_user){
          $content.="<option value='$key'>$select_to_user</option>";
    }
    $content.='</select>';
    $content.='</div></form>';
    $content.='</div>';
}
print_collapsible_region_start(' ', 'filters_form', ' '.' '.get_string('filters'), false, $collapse);
$mform->display();
print_collapsible_region_end();
if ($course) {
  $select_div='<div class="col-md-12 p-0">
    <div class="col-md-12">'.$content.'</div>
  </div>';
  echo $select_div;
  $myJSON = json_encode($options);
  echo "<script language='javascript'>

  $( document ).ready(function() {
    $('#select_remove').click(function() {
        $('#bootstrap-duallistbox-selected-list_duallistbox_courses_users option').prop('selected', true);
        $('.box3 .remove').prop('disabled', false);
        $('#user_unassign_all').val('Remove_All_Users');

        $('.box3 .move').prop('disabled', true);
        $('#bootstrap-duallistbox-nonselected-list_duallistbox_courses_users option').prop('selected', false);
        $('#user_assign_all').val('Add Selected Users');

    });
    $('#remove_select').click(function() {
        $('#bootstrap-duallistbox-selected-list_duallistbox_courses_users option').prop('selected', false);
        $('.box3 .remove').prop('disabled', true);
        $('#user_unassign_all').val('Remove Selected Users');
    });
    $('#select_add').click(function() {
        $('#bootstrap-duallistbox-nonselected-list_duallistbox_courses_users option').prop('selected', true);
        $('.box3 .move').prop('disabled', false);
        $('#user_assign_all').val('Add_All_Users');

        $('.box3 .remove').prop('disabled', true);
        $('#bootstrap-duallistbox-selected-list_duallistbox_courses_users option').prop('selected', false);
        $('#user_unassign_all').val('Remove Selected Users');
        
    });
    $('#add_select').click(function() {
       $('#bootstrap-duallistbox-nonselected-list_duallistbox_courses_users option').prop('selected', false);
        $('.box3 .move').prop('disabled', true);
        $('#user_assign_all').val('Add Selected Users');
    });
    $('#bootstrap-duallistbox-selected-list_duallistbox_courses_users').on('change', function() {
        if(this.value!=''){
            $('.box3 .remove').prop('disabled', false);
            $('.box3 .move').prop('disabled', true);
        }
    });
    $('#bootstrap-duallistbox-nonselected-list_duallistbox_courses_users').on('change', function() {
        if(this.value!=''){
            $('.box3 .move').prop('disabled', false);
            $('.box3 .remove').prop('disabled', true);
        }
    });
    jQuery(
        function($)
        {
          $('.dual_select').bind('scroll', function()
            {
              if($(this).scrollTop() + $(this).innerHeight()>=$(this)[0].scrollHeight)
              {
                var get_id=$(this).attr('id');
                if(get_id=='bootstrap-duallistbox-selected-list_duallistbox_courses_users'){
                    var type='remove';
                    var total_users=$select_from_users_total;
                }
                if(get_id=='bootstrap-duallistbox-nonselected-list_duallistbox_courses_users'){
                    var type='add';
                    var total_users=$select_to_users_total;
                   
                }
                var count_selected_list=$('#'+get_id+' option').length;
               
                var lastValue = $('#'+get_id+' option:last-child').val();
             
              if(count_selected_list<total_users){  
                   //alert('end reached');
                    var selected_list_request = $.ajax({
                        method: 'GET',
                        url: M.cfg.wwwroot + '/local/mooccourses/courseenrol.php?options=$myJSON',
                        data: {id:'$course_id',sesskey:'$sesskey', type:type,view:'ajax',lastitem:lastValue,enrolid:'$enrolid'},
                        dataType: 'html'
                    });  
                    var appending_selected_list = '';
                    selected_list_request.done(function(response){
                    //console.log(response);
                    response = jQuery.parseJSON(response);
                    //console.log(response);
                  
                    $.each(response, function (index, data) {
                   
                        appending_selected_list = appending_selected_list + '<option value=' + index + '>' + data + '</option>';
                    });
                    $('#'+get_id+'').append(appending_selected_list);
                    });
                }
              }
            })
        }
    );
 
  });
    </script>";
}
$backurl = new moodle_url('/local/mooccourses/index.php');
$continue='<div class="col-md-12 pull-right text-right mt-15px">';
$continue.=$OUTPUT->single_button($backurl,get_string('continue'));
$continue.='</div>';
echo $continue;
echo $OUTPUT->footer();
