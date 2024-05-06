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
 * Take attendance in curriculum.
 *
 * @package    local_curriculum
 * @copyright  2017 M Arun Kumar <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/program/lib.php');

use \local_program\program as program;
use core_component;

$curriculumid = required_param('ccid', PARAM_INT);
$sessionid = required_param('sid', PARAM_INT);
$semesterid = optional_param('semesterid', 0, PARAM_INT);
$bclcid = optional_param('bclcid', 0, PARAM_INT);
$programid = optional_param('programid', 0, PARAM_INT);
$yearid = optional_param('yearid', 0, PARAM_INT);
$ccses_action = optional_param('ccses_action', '', PARAM_RAW);
// print_object($sessionid);exit;
// $PAGE->requires->jquery();
// $PAGE->requires->jquery_plugin('ui');

$returnurl = optional_param('returnurl', null, PARAM_LOCALURL);
$submit_value = optional_param('submit_value', '', PARAM_RAW);
$add = optional_param_array('add', array(), PARAM_RAW);
$remove = optional_param_array('remove', array(), PARAM_RAW);
$view = optional_param('view', 'page', PARAM_RAW);
$type = optional_param('type', '', PARAM_RAW);
$lastitem = optional_param('lastitem', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);

$url = new moodle_url($CFG->wwwroot . '/local/program/session_enrolusers.php',
    array('ccid' => $curriculumid));
if ($semesterid > 0) {
    $url->param('semesterid', $semesterid);
}
if ($programid > 0) {
    $url->param('programid', $programid);
}
if ($yearid > 0) {
    $url->param('yearid', $yearid);
}
if ($bclcid > 0) {
    $url->param('bclcid', $bclcid);
}
if ($sessionid > 0) {
    $url->param('sid', $sessionid);
}
if ($ccses_action != null) {
    $url->param('ccses_action', $ccses_action);
}
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$curriculum_name = $DB->get_field('local_curriculum','name',array('id' => $curriculumid));
$session_name = $DB->get_field('local_cc_course_sessions','name',array('id' => $sessionid));
$PAGE->navbar->add(get_string("pluginname", 'local_program'), new moodle_url('view.php',
    array('ccid' => $curriculumid, 'sid' => $sessionid)));
$PAGE->navbar->add($curriculum_name);
$PAGE->set_title($curriculum_name);
$PAGE->set_heading(get_string('session_enrollusers', 'local_program', $session_name));
$renderer = $PAGE->get_renderer('local_program');
$curriculumclass = new program();
require_login();

// require_capability('local/program:manageprogram', $context);
require_capability('local/program:manageusers', $context);
// print_object($view);
// if ($view == 'ajax') {
//     $options = (array)json_decode($_GET["options"], false);
//      $select_from_users = (new program)->select_to_and_from_users_sessions($type, $curriculumid, , $options,false, $offset1=-1, $perpage=50, $lastitem);
//     echo json_encode($select_from_users);
//     exit;
// }
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->js('/local/program/js/jquery.bootstrap-duallistbox.js',true);
$PAGE->requires->css('/local/program/css/bootstrap-duallistbox.css');
$PAGE->requires->js_call_amd('local_program/program', 'init',
    array(array('curriculumid' => $curriculumid)));
$pageurl = new moodle_url($url);
if ($returnurl) {
    $url->param('returnurl', $returnurl);
}
$data_submitted = data_submitted();
if ($curriculumid && $sessionid) {
    // $organization = null;
    // $department   = null;
    $email        = null;
    $idnumber     = null;
    $uname        = null;
    $groups       = null;

    if (file_exists($CFG->dirroot . '/local/lib.php')) {
        require_once($CFG->dirroot . '/local/lib.php');
        $filterlist = get_filterslist();
    }
    if (!empty($courses_plugin_exists)) {
        $mform = new filters_form($url, array('filterlist' => $filterlist));
        if ($mform->is_cancelled()) {
            redirect($PAGE->url);
        } else {
            $filterdata = $mform->get_data();
            if ($filterdata) {
                $collapse = false;
            } else {
                $collapse = true;
            }
            // $organization = !empty($filterdata->organizations) ? implode(',', $filterdata->organizations) : null;
            // $department = !empty($filterdata->departments) ? implode(',', $filterdata->departments) : null;
            $email = !empty($filterdata->email) ? implode(',', $filterdata->email) : null;
            $idnumber = !empty($filterdata->idnumber) ? implode(',', $filterdata->idnumber) : null;
            $uname = !empty($filterdata->users) ? implode(',', $filterdata->users) : null;
            $groups = !empty($filterdata->groups) ? implode(',', $filterdata->groups) : null;
        }
    }

    // Create the user selector objects.
    $options = array('context' => $context->id, 'curriculumid' => $curriculumid, 'email' => $email,
        'idnumber' => $idnumber, 'uname' => $uname, 'groups' => $groups, 'ccses_action' => $ccses_action);
    //$potentialuserselector = new local_curriculum_potential_users('addselect', $options);
    //$currentuserselector = new local_curriculum_existing_users('removeselect', $options);

    if ($add && confirm_sesskey()) {
        if ($submit_value == "Add_All_Users") {
            $options = (array)json_decode($_REQUEST["options"], false);
            $userstoassign = array_flip((new program)->select_to_and_from_users_sessions('add',
                $curriculumid, $programid, $semesterid, $bclcid, $sessionid, $ccses_action,$options, false, $offset1=-1, $perpage=-1));
        } else {
            $userstoassign = $add;
        }

        if (!empty($userstoassign)) {
            $curriculumclass->session_add_assignusers($curriculumid, $programid, $yearid, $semesterid, $bclcid, $sessionid, $ccses_action, $userstoassign);
        }
        redirect($PAGE->url);
    }
    if ($remove && confirm_sesskey()) {
        if ($submit_value == "Remove_All_Users") {
            $options = (array)json_decode($_REQUEST["options"], false);
            $userstounassign = array_flip((new program)->select_to_and_from_users_sessions('remove',
                $curriculumid, $programid, $semesterid, $bclcid, $sessionid, $ccses_action,$options, false, $offset1=-1, $perpage=-1));
        } else {
            $userstounassign = $remove;
        }
        if (!empty($userstounassign)) {
            $curriculumclass->session_remove_assignusers($curriculumid, $programid, $yearid, $semesterid, $bclcid, $sessionid, $ccses_action, $userstounassign);
        }
        redirect($PAGE->url);
    }
    $select_to_users = (new program)->select_to_and_from_users_sessions('add', $curriculumid, $programid, $semesterid, $bclcid, $sessionid, $ccses_action, $options, false, $offset=-1, $perpage=50);
    $select_to_users_total = (new program)->select_to_and_from_users_sessions('add', $curriculumid, $programid, $semesterid, $bclcid, $sessionid, $ccses_action, $options, true, $offset1=-1, $perpage=-1);
    $select_from_users = (new program)->select_to_and_from_users_sessions('remove', $curriculumid, $programid, $semesterid, $bclcid, $sessionid, $ccses_action, $options,false, $offset1=-1, $perpage=50);
    $select_from_users_total = (new program)->select_to_and_from_users_sessions('remove', $curriculumid, $programid, $semesterid, $bclcid, $sessionid, $ccses_action,
        $options, true, $offset1=-1, $perpage=-1);

    $select_all_enrolled_users = '&nbsp&nbsp<button type="button" id="select_add" name="select_all" value="Select All" title="Select All"/ class="btn btn-default">' . get_string('select_all', 'local_program') . '</button>';
    $select_all_enrolled_users.='&nbsp&nbsp<button type="button" id="add_select" name="remove_all" value="Remove All" title="Remove All" class="btn btn-default"/>' . get_string('remove_all', 'local_program') . '</button>';

    $select_all_not_enrolled_users='&nbsp&nbsp<button type="button" id="select_remove" name="select_all" value="Select All" title="Select All" class="btn btn-default"/>' . get_string('select_all', 'local_program') . '</button>';
    $select_all_not_enrolled_users.='&nbsp&nbsp<button type="button" id="remove_select" name="remove_all" value="Remove All" title="Remove All" class="btn btn-default"/>' . get_string('remove_all', 'local_program') . '</button>';

    $content = '<div class="bootstrap-duallistbox-container">';
    $content .= '<form  method="post" name="form_name" id="user_assign" class="form_class" ><div class="box2 col-md-6">
        <input type="hidden" name="ccid" value="'.$curriculumid.'"/>
        <input type="hidden" name="programid" value="'.$programid.'"/>
        <input type="hidden" name="sesskey" value="'.sesskey().'"/>
        <input type="hidden" name="options"  value='.json_encode($options).' />
        <label>' . get_string('enrolled_users', 'local_program', $select_from_users_total) .
        $select_all_not_enrolled_users . '</label>
   <span class="info-container">
        <span class="info"></span>
            <button type="button" class="custom_btn btn clear2 pull-right"></button>
        </span>
   <div class="custom_btn btn-group buttons">
        <button type="submit" class="custom_btn btn remove btn-default" disabled="disabled" title="Remove Selected Users" name="submit_value" value="Remove Selected Users"/>
       '.get_string('remove_selected_users', 'local_program').'
        </button>
         <button type="submit" class="custom_btn btn removeall btn-default" disabled="disabled" title="Remove All Users" name="submit_value" value="Remove_All_Users"/>
         '.get_string('remove_all_users', 'local_program').'
         </button>
   </div>';
    $content .= '<select multiple="multiple" name="remove[]" id="bootstrap-duallistbox-selected-list_duallistbox_curriculum_users" class="dual_select">';
    foreach($select_from_users as $key=>$select_from_user){
        // $presentsessions = $DB->count_records('local_cc_session_signups',
        //             array('curriculumid' => $curriculumid,
        //                 'semesterid' => $semesterid, 'bclcid' => $bclcid,
        //                 'userid' => $key, 'sessionid' => $sessionid, 'completion_status' => 1));
        $params = array();
        $params['curriculumid'] = $curriculumid;
        $params['semesterid'] = $semesterid;
        $params['bclcid'] = $bclcid;
        $params['userid'] = $key;
        $params['sessionid'] = $sessionid;
        $params['status'] = 0;
        $presentsessions = $DB->count_records_sql("SELECT count(id) FROM {local_cc_session_signups} WHERE curriculumid = :curriculumid AND semesterid = :semesterid AND bclcid = :bclcid AND userid = :userid AND sessionid = :sessionid AND completion_status != :status", $params);
        if($presentsessions > 0){
            $disable = "disabled";
        }else{
            $disable = " ";
        }
        $content .= "<option value='$key' $disable = $disable>$select_from_user</option>";
    }

    $content.='</select>';
    // print_object($content);exit;
    $content.='</div></form>';
    $content.='<form  method="post" name="form_name" id="user_un_assign" class="form_class" ><div class="box1 col-md-6">
    <input type="hidden" name="ccid" value="'.$curriculumid.'"/>
    <input type="hidden" name="programid" value="'.$programid.'"/>
    <input type="hidden" name="sesskey" value="'.sesskey().'"/>
    <input type="hidden" name="options"  value='.json_encode($options).' />
   <label> '.get_string('not_enrolled_users', 'local_program',$select_to_users_total).$select_all_enrolled_users.'</label>
        <span class="info-container">
            <span class="info"></span>
                <button type="button" class="custom_btn btn clear1 pull-right"></button>
            </span>
    <div class="custom_btn btn-group buttons">

        <button type="submit" class="custom_btn btn moveall btn-default" disabled="disabled" title="Add All Users" name="submit_value" value="Add_All_Users"/>
        '.get_string('add_all_users', 'local_program').'
        </button>

        <button type="submit" class="custom_btn btn move btn-default" disabled="disabled" title="Add Selected Users" name="submit_value" value="Add Selected Users"/>
       '.get_string('add_selected_users', 'local_program').'
        </button>

    </div>';
    $content.='<select multiple="multiple" name="add[]" id="bootstrap-duallistbox-nonselected-list_duallistbox_curriculum_users" class="dual_select">';
    foreach($select_to_users as $key=>$select_to_user){
          $content.="<option value='$key'>$select_to_user</option>";
    }
    $content.='</select>';
    $content.='</div></form>';
    $content.='</div>';
}
echo $OUTPUT->header();
if (!empty($courses_plugin_exists)) {
    print_collapsible_region_start(' ', 'filters_form', ' ' . ' ' . get_string('filters'), false, $collapse);
    $mform->display();
    print_collapsible_region_end();
}
if ($curriculumid) {
    $session_capacity_check=(new program)->session_capacity_check($curriculumid, $semesterid, $bclcid, $sessionid);
        $capacity_check =  '';
        if($session_capacity_check){
        $capacity_check = '<div class="w-full pull-left">'.get_string('capacity_check', 'local_program').'</div>';
    }
    $select_div = '<div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">'.$capacity_check.$content.'</div>
                   </div>';
echo $select_div;
$myJSON = json_encode($options);
echo "<script language='javascript'>

$( document ).ready(function() {
    $('#select_remove').click(function() {
        $('#bootstrap-duallistbox-selected-list_duallistbox_curriculum_users option').prop('selected', true);
        //$('.box2 .removeall').addClass('submit_button');
        $('.box2 .removeall').prop('disabled', false);
        $('.box2 .remove').prop('disabled', true);

        $('.box1 .moveall').prop('disabled', true);
        $('.box1 .move').prop('disabled', true);
        $('#bootstrap-duallistbox-nonselected-list_duallistbox_curriculum_users option').prop('selected', false);

    });
    $('#remove_select').click(function() {
        $('#bootstrap-duallistbox-selected-list_duallistbox_curriculum_users option').prop('selected', false);
        $('.box2 .removeall').prop('disabled', true);
        //$('.box2 .removeall').removeClass('submit_button');
        $('.box2 .remove').prop('disabled', true);

        $('.box1 .moveall').prop('disabled', true);
        $('.box1 .move').prop('disabled', true);
        $('#bootstrap-duallistbox-nonselected-list_duallistbox_curriculum_users option').prop('selected', false);

    });
    $('#select_add').click(function() {
        $('#bootstrap-duallistbox-nonselected-list_duallistbox_curriculum_users option').prop('selected', true);
        //$('.box1 .moveall').addClass('submit_button');
        $('.box1 .moveall').prop('disabled', false);
        $('.box1 .move').prop('disabled', true);

        $('.box2 .removeall').prop('disabled', true);
        $('.box2 .remove').prop('disabled', true);
         $('#bootstrap-duallistbox-selected-list_duallistbox_curriculum_users option').prop('selected', false);
    });
    $('#add_select').click(function() {
        $('#bootstrap-duallistbox-nonselected-list_duallistbox_curriculum_users option').prop('selected',false);
        $('.box1 .moveall').prop('disabled', true);
        //$('.box1 .moveall').removeClass('submit_button');
        $('.box1 .move').prop('disabled', true);

        $('.box2 .removeall').prop('disabled', true);
        $('.box2 .remove').prop('disabled', true);
         $('#bootstrap-duallistbox-selected-list_duallistbox_curriculum_users option').prop('selected', false);
    });
    $('#bootstrap-duallistbox-selected-list_duallistbox_curriculum_users').on('change', function() {
        if(this.value!=''){
            $('.box2 .remove').prop('disabled', false);
            //$('.box2 .remove').addClass('submit_button');
            $('.box2 .removeall').prop('disabled', true);

            $('.box1 .moveall').prop('disabled', true);
            $('.box1 .move').prop('disabled', true);
            $('#bootstrap-duallistbox-nonselected-list_duallistbox_curriculum_users option').prop('selected', false);
        }
    });
    $('#bootstrap-duallistbox-nonselected-list_duallistbox_curriculum_users').on('change', function() {
        if(this.value!=''){
            $('.box1 .move').prop('disabled', false);
            //$('.box1 .move').addClass('submit_button');
            $('.box1 .moveall').prop('disabled', true);

            $('.box2 .removeall').prop('disabled', true);
            $('.box2 .remove').prop('disabled', true);
            $('#bootstrap-duallistbox-selected-list_duallistbox_curriculum_users option').prop('selected', false);
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
                if(get_id=='bootstrap-duallistbox-selected-list_duallistbox_curriculum_users'){
                    var type='remove';
                    var total_users=$select_from_users_total;
                }
                if(get_id=='bootstrap-duallistbox-nonselected-list_duallistbox_curriculum_users'){
                    var type='add';
                    var total_users=$select_to_users_total;

                }
                var count_selected_list=$('#'+get_id+' option').length;

                var lastValue = $('#'+get_id+' option:last-child').val();

              if(count_selected_list<total_users){
                   //alert('end reached');
                    var selected_list_request = $.ajax({
                        method: 'GET',
                        url: M.cfg.wwwroot + '/local/program/enrollusers.php?options=$myJSON',
                        data: {ccid:'$curriculumid',sesskey:'$sesskey', type:type,view:'ajax',lastitem:lastValue},
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
$continue = '<div class="col-md-1 pull-right">';
$continue .= '<a href='.$CFG->wwwroot.'/local/program/sessions.php?bclcid='.$bclcid.''.'&semesterid='.$semesterid.''.'&ccses_action='.$ccses_action.'&ccid='.$curriculumid.'&programid='.$programid.'&yearid='.$yearid.' class="singlebutton"><button>'.get_string('continue', 'local_program').'</button></a>';
$continue .= '</div>';
echo $continue;
echo $OUTPUT->footer();