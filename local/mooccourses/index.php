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
 * List the tool provided in a course
 *
 * @package    local
 * @subpackage mooccourses
 * @copyright  2017 Eabyas Info Solutions <www.eabyas.in> 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');

global $CFG, $USER, $PAGE, $OUTPUT;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/local/mooccourses/lib.php');
// require_once($CFG->dirroot . '/local/mooccourses/filter_form.php');
require_login();
// <sandeep> - SRIVSPT-3 Manage Mooc Courses Module Changes -- Starts
$type = optional_param('type',2, PARAM_RAW);
// <sandeep> - SRIVSPT-3 Manage Mooc Courses Module Changes -- Ends

$systemcontext = context_system::instance();
if(!has_capability('local/mooccourses:view', $systemcontext)){
	print_error("You don't have permissions to view this page.");
}
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/mooccourses/index.php');
$PAGE->set_heading(get_string('manage_mooc_courses', 'local_mooccourses'));
$PAGE->set_title(get_string('manage_mooc_courses', 'local_mooccourses'));
$corecomponent = new \core_component();
$PAGE->requires->js_call_amd('local_mooccourses/courseAjaxform', 'load');
$PAGE->requires->css('/local/mooccourses/css/jquery.dataTables.css');
// $PAGE->requires->css('/local/mooccourses/css/style.css');
// $PAGE->requires->js_call_amd('local_mooccourses/mooccourses', 'soldcoursesDatatable', array());
$PAGE->requires->js_call_amd('local_mooccourses/newmooccourses', 'load', array());
$PAGE->requires->js_call_amd('local_departments/newdepartment', 'load', array());
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('manage_mooc_courses','local_mooccourses')); 
$url = new moodle_url($CFG->wwwroot . '/local/courses/courses.php', array('type'=>$type));
$output = $PAGE->get_renderer('local_mooccourses');
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 10, PARAM_INT);

echo $OUTPUT->header();

$output->top_action_buttons();
echo '<div class="col-12 page-desc"><b class="page-hrdesc">Description:</b><br>This page displays list of courses';
echo '</div>';
 
require_once($CFG->dirroot . '/local/courses/filters_form.php');
if(is_siteadmin() OR has_capability('local/costcenter:manage_multiorganizations', $systemcontext)) {
    $mform = new filters_form(null, array('filterlist'=>array('organizations','department'),'action' => 'user_enrolment'));
}elseif(has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
	$mform = new filters_form(null, array('filterlist'=>array('department'),'action' => 'user_enrolment'));
}

if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext) || has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
    if($mform->is_cancelled()) {
        redirect($CFG->wwwroot . '/local/mooccourses/index.php');
    }else{
        $filterdata =  $mform->get_data();
        if($filterdata){
            $collapse = false;
        } else{
            $collapse = true;
        }
    }
    $heading = '<span class="filter-lebel">'.get_string('filters').'</span>';

    print_collapsible_region_start(' ', 'filters_form', ' '.' '.$heading, false, $collapse);
    $mform->display();
    print_collapsible_region_end();
} 
if (is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext) || has_capability('local/costcenter:manage_ownorganization', $systemcontext)) {
    echo $output->tabtrees($type);
    $filterdata->mode = $type;
}else if(has_capability('local/costcenter:manage_owndepartments',$systemcontext)){
    $filterdata->mode = 2;
}else{
	$filterdata->mode = 3;
}

echo $output->mooccourses_view($filterdata,$type, $page, $perpage);
echo $OUTPUT->footer();
