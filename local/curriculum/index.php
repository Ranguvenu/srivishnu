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
 * @subpackage curriculum
 * @copyright  2017 Eabyas Info Solutions <www.eabyas.in> 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');


global $CFG, $USER, $PAGE, $OUTPUT;

require_login();

$systemcontext = context_system::instance();
/*if(is_siteadmin() || !has_capability('local/program:manageprogram', $systemcontext) || !has_capability('local/costcenter:manage_multiorganizations', $systemcontext) || has_capability('local/costcenter:manage_ownorganization', $systemcontext)){
	print_error("You don't have permissions to view this page.");
}*/
$PAGE->set_pagelayout('admin');
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/curriculum/index.php');
$PAGE->set_heading(get_string('manage_curriculum', 'local_curriculum'));
$PAGE->set_title(get_string('pluginname', 'local_curriculum'));
$corecomponent = new \core_component();
$PAGE->requires->css('/local/mooccourses/css/jquery.dataTables.css');
$PAGE->requires->js_call_amd('local_curriculum/ajaxforms', 'load', array());
$PAGE->requires->js_call_amd('local_departments/newdepartment', 'load', array());

$PAGE->navbar->ignore_active();
$url = $CFG->wwwroot.'/local/curriculum/index.php';
$PAGE->navbar->add(get_string('manage_curriculum','local_curriculum')); 

$output       = $PAGE->get_renderer('local_curriculum');
$page         = optional_param('page', 0, PARAM_INT);
$perpage      = optional_param('perpage', 10, PARAM_INT);

require_once($CFG->dirroot . '/local/courses/filters_form.php');
require_once('lib.php');



echo $OUTPUT->header();

$output->top_action_buttons();
echo '<div class="col-12 page-desc"><b class="page-hrdesc">Description:</b><br>This page displays list of Curriculums';

if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
	$mform = new filters_form(null, array('filterlist'=>array('organizations','department'),'action'=>'user_enrolment'));
	if ($mform->is_cancelled()) {
		redirect($CFG->wwwroot . '/local/curriculum/index.php');
	} else{
		$filterdata =  $mform->get_data();
		if($filterdata){
			$collapse = false;
		} else{
			$collapse = true;
		}
	}
	print_collapsible_region_start('curriculum_filter', 'filters_form', ' ' . ' ' . get_string('filters'), false, $collapse);
	$mform->display();
	print_collapsible_region_end();
}
 echo $output->curriculum_view($filterdata,$page,$perpage);
 echo $OUTPUT->footer();
