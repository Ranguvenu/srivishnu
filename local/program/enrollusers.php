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
 * Manage users in curriculum.
 *
 * @package    local_curriculum
 * @copyright  2017 M Arun Kumar <arun@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use \local_program\program as program;
use core_component;
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/program/lib.php');
$core_component = new core_component();
$courses_plugin_exists = $core_component::get_plugin_directory('local', 'courses');
if (!empty($courses_plugin_exists)) {
    require_once($CFG->dirroot . '/local/courses/filters_form.php');
}

$curriculumid = required_param('ccid', PARAM_INT);
$returnurl = optional_param('returnurl', null, PARAM_LOCALURL);
$submit_value = optional_param('submit_value', '', PARAM_RAW);
$add = optional_param_array('add', array(), PARAM_RAW);
$remove = optional_param_array('remove', array(), PARAM_RAW);
$view = optional_param('view', 'page', PARAM_RAW);
$type = optional_param('type', '', PARAM_RAW);
$lastitem = optional_param('lastitem', 0, PARAM_INT);

$url = new moodle_url('/local/program/enrollusers.php', array('ccid' => $curriculumid));
$curriculum = $DB->get_record('local_curriculum', array('id' => $curriculumid));
if (empty($curriculum)) {
    print_error('curriculum not found!');
}
$context = context_system::instance();
$sesskey = sesskey();
$curriculumclass = new program();
// Security.
require_login();
require_capability('local/program:manageprogram', $context);
require_capability('local/program:manageusers', $context);
if ($view == 'ajax') {
    $options = (array)json_decode($_GET["options"], false);
     $select_from_users = (new program)->select_to_and_from_users($type, $curriculumid, $options,false, $offset1=-1, $perpage=50, $lastitem);
    echo json_encode($select_from_users);
    exit;
}
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
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title($curriculum->name);
$PAGE->set_pagelayout('admin');
$data_submitted = data_submitted();
if ($curriculumid) {
    $organization = null;
    $department   = null;
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
            $organization = !empty($filterdata->organizations) ? implode(',', $filterdata->organizations) : null;
            $department = !empty($filterdata->departments) ? implode(',', $filterdata->departments) : null;
            $email = !empty($filterdata->email) ? implode(',', $filterdata->email) : null;
            $idnumber = !empty($filterdata->idnumber) ? implode(',', $filterdata->idnumber) : null;
            $uname = !empty($filterdata->users) ? implode(',', $filterdata->users) : null;
            $groups = !empty($filterdata->groups) ? implode(',', $filterdata->groups) : null;
        }
    }

    // Create the user selector objects.
    $options = array('context' => $context->id, 'curriculumid' => $curriculumid,
        'organization' => $organization, 'department' => $department, 'email' => $email,
        'idnumber' => $idnumber, 'uname' => $uname, 'groups' => $groups);
    //$potentialuserselector = new local_curriculum_potential_users('addselect', $options);
    //$currentuserselector = new local_curriculum_existing_users('removeselect', $options);

    if ($add && confirm_sesskey()) {
        if ($submit_value == "Add_All_Users") {
            $options = (array)json_decode($_REQUEST["options"], false);
            $userstoassign = array_flip((new program)->select_to_and_from_users('add',
                $curriculumid,$options, false, $offset1=-1, $perpage=-1));
        } else {
            $userstoassign = $add;
        }

        if (!empty($userstoassign)) {
            $curriculumclass->curriculum_add_assignusers($curriculumid, $userstoassign);
        }
        redirect($PAGE->url);
    }
    if ($remove && confirm_sesskey()) {
        if ($submit_value == "Remove_All_Users") {
            $options = (array)json_decode($_REQUEST["options"], false);
            $userstounassign = array_flip((new program)->select_to_and_from_users('remove',
                $curriculumid, $options, false, $offset1=-1, $perpage=-1));
        } else {
            $userstounassign = $remove;
        }
        if (!empty($userstounassign)) {
            $curriculumclass->curriculum_remove_assignusers($curriculumid, $userstounassign);
        }
        redirect($PAGE->url);
    }

    $select_to_users = (new program)->select_to_and_from_users('add', $curriculumid, $options, false, $offset=-1, $perpage=50);
    $select_to_users_total = (new program)->select_to_and_from_users('add', $curriculumid, $options, true, $offset1=-1, $perpage=-1);
    $select_from_users = (new program)->select_to_and_from_users('remove', $curriculumid, $options,false, $offset1=-1, $perpage=50);
    $select_from_users_total = (new program)->select_to_and_from_users('remove', $curriculumid,
        $options, true, $offset1=-1, $perpage=-1);

    $select_all_enrolled_users = '&nbsp&nbsp<button type="button" id="select_add" name="select_all" value="Select All" title="Select All"/ class="btn btn-default">' . get_string('select_all', 'local_program') . '</button>';
    $select_all_enrolled_users.='&nbsp&nbsp<button type="button" id="add_select" name="remove_all" value="Remove All" title="Remove All" class="btn btn-default"/>' . get_string('remove_all', 'local_program') . '</button>';

    $select_all_not_enrolled_users='&nbsp&nbsp<button type="button" id="select_remove" name="select_all" value="Select All" title="Select All" class="btn btn-default"/>' . get_string('select_all', 'local_program') . '</button>';
    $select_all_not_enrolled_users.='&nbsp&nbsp<button type="button" id="remove_select" name="remove_all" value="Remove All" title="Remove All" class="btn btn-default"/>' . get_string('remove_all', 'local_program') . '</button>';

    $content = '<div class="bootstrap-duallistbox-container">';
    $content .= '<form  method="post" name="form_name" id="user_assign" class="form_class" ><div class="box2 col-md-6">
        <input type="hidden" name="ccid" value="'.$curriculumid.'"/>
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
        $content .= "<option value='$key'>$select_from_user</option>";
    }

    $content.='</select>';
    $content.='</div></form>';
    $content.='<form  method="post" name="form_name" id="user_un_assign" class="form_class" ><div class="box1 col-md-6">
    <input type="hidden" name="ccid" value="'.$curriculumid.'"/>
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
$PAGE->navbar->add(get_string("pluginname", 'local_program'), new moodle_url('view.php',array('ccid'=>$curriculum->id)));
$PAGE->navbar->add($curriculum->name);
$PAGE->set_heading(get_string('assignusers_heading', 'local_program',$curriculum->name));
echo $OUTPUT->header();

if (!empty($courses_plugin_exists)) {
    print_collapsible_region_start(' ', 'filters_form', ' ' . ' ' . get_string('filters'), false, $collapse);
    $mform->display();
    print_collapsible_region_end();
}
if ($curriculumid) {

    $assignurl = new moodle_url($PAGE->url, array('ccid' => $curriculumid));
    $select_div = '<div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">'.$content.'</div>
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

  $continue='<div class="col-md-12 pull-right text-xs-right mt-15">';
  $continue.='<a href='.$CFG->wwwroot.'/local/program/view.php?ccid='.$curriculumid.'&prgid='.$curriculum->program.' class="singlebutton"><button class="btn">'.get_string('continue', 'local_program').'</button></a>';
  $continue.='</div>';
  echo $continue;
echo $OUTPUT->footer();
