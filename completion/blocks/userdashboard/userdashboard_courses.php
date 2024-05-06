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
 * Handle selection changes and actions on the competency tree.
 *
 * @module     block_userdasboard
 * @package    block_userdasboard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once dirname(__FILE__) . '/../../config.php';
require_once $CFG->dirroot . '/blocks/userdashboard/renderer.php';
require_login();
global $DB, $PAGE, $CFG, $USER, $OUTPUT;
$tab = required_param('tab',  PARAM_TEXT);
$subtab = required_param('subtab',  PARAM_TEXT);
$systemcontext = context_system::instance();
$pageurl = new moodle_url('/blocks/userdashboard/userdashboard_courses.php',array('tab' => $tab, 'subtab' => $subtab));
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_context($systemcontext);
$PAGE->set_title($tab.' '.$subtab);
$PAGE->set_heading(get_string($tab,'block_userdashboard'));
$PAGE->navbar->add(get_string($tab,'block_userdashboard'));
$PAGE->requires->js_call_amd('block_userdashboard/userdashboardinit', 'init');
$PAGE->requires->js_call_amd('block_userdashboard/userdashboardinit', 'makeActive',array('tab' => $subtab));
switch($tab){
	case 'elearning_courses':
		$renderable = new block_userdashboard\output\elearning_courses($subtab,'');
		break;
	case 'program_courses':
		$renderable = new block_userdashboard\output\program_courses($subtab,'');
		break;
}
echo $OUTPUT->header();
$output = $PAGE->get_renderer('block_userdashboard');
$data = $renderable->export_for_template($output);
$data->inprogress_elearning = json_decode($data->inprogress_elearning);
//$data->enableslider = 0;
$content = $OUTPUT->render_from_template('block_userdashboard/userdashboard_courses', $data);
echo '<div class = "divslide">'.$content.'</div>';
echo $OUTPUT->footer();
// $renderable = new block_userdashboard\output\elearning_courses($subtab,'');
