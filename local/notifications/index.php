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
 * Version information
 *
 * @package    local_notifications
 * @copyright  2018 sreenivas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/courses/filters_form.php');
global $CFG, $USER, $PAGE, $OUTPUT;
$id = optional_param('id', 0, PARAM_INT);
$deleteid = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$sitecontext = context_system::instance();
require_login();
$PAGE->set_url('/local/notifications/index.php', array());
$PAGE->set_context($sitecontext);
$PAGE->set_title(get_string('pluginname', 'local_notifications'));
$PAGE->set_heading(get_string('pluginname', 'local_notifications'));
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->css('/local/notifications/css/jquery.dataTables.min.css', true);
$renderer = $PAGE->get_renderer('local_notifications');

echo $OUTPUT->header();
if($deleteid && $confirm && confirm_sesskey()){	
	$result = $DB->delete_records("local_notification_info", array('id'=>$deleteid));
	if($result){
		redirect($CFG->wwwroot.'/local/notifications/index.php');
	}
}
$PAGE->requires->js_call_amd('local_notifications/notifications', 'load');
$systemcontext = context_system::instance();

// $PAGE->requires->js_call_amd('local_notifications/notifications', 'init', array('[data-action=createnotificationmodal]', $sitecontext->id, $id));
echo "<ul class='course_extended_menu_list'>
        <li>
        	<div class='coursebackup course_extended_menu_itemcontainer'>
	                    <a id='extended_menu_createusers' title='".get_string('createnotification', 'local_notifications')."' class='course_extended_menu_itemlink' data-action='createnotificationmodal' onclick ='(function(e){ require(\"local_notifications/notifications\").init({selector:\"createnotificationmodal\", context:$sitecontext->id, id:$id, form_status:0}) })(event)' ><i class='icon fa fa-bell-o createicon' aria-hidden='true'></i><i class='fa fa-plus createiconchild' aria-hidden='true'></i></a>
  	        </div>
        </li>
    </ul>";
echo '<div class="col-12 page-desc"><b class="page-hrdesc">Description:</b><br>
This page will list all the Email notification templates configured for a university/universities. Templates for email notifications like Course enrollment, Semester completion, program completions etc. can be defined.
</div>';
$PAGE->requires->js_call_amd('local_notifications/custom', 'init');
$PAGE->requires->js_call_amd('local_notifications/custom', 'notificationDatatable', array(array('id' => $id, 'context' => $sitecontext)));
if(is_siteadmin() || has_capability('local/costcenter:manage_multiorganizations', $systemcontext)){
	$mform = new filters_form(null, array('filterlist'=>array('organizations'),'action' => 'user_enrolment'));
	if ($mform->is_cancelled()) {
		redirect($CFG->wwwroot . '/local/notifications/index.php');
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
$notifications = new \local_notifications\output\notifications();
$notifications->filterdata = $filterdata;
echo $renderer->render($notifications);

echo $OUTPUT->footer();
