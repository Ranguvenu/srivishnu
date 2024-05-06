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
 * List the tool provided 
 *
 * @package   local
 * @subpackage  users
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

use local_users\output\myteam;
use local_users\output\courseallocation;
use local_users\output\team_approvals;

global $DB, $OUTPUT, $USER, $PAGE;

// $supervisor = $DB->get_field('user', 'id', array('open_supervisorid' => $USER->id));
$supervisor = $DB->record_exists('user', array('open_supervisorid' => $USER->id));
if(empty($supervisor)){
  print_error('nopermissiontoviewpage');
}

$systemcontext = context_system::instance();
require_login();
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/users/team.php');
$PAGE->set_pagelayout('standard');

//Header and the navigation bar
$PAGE->set_title(get_string('team_dashboard', 'local_users'));
$PAGE->set_heading(get_string('myteam', 'local_users'));
$PAGE->navbar->add(get_string('myteam', 'local_users'));

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->js('/local/users/js/jquery.dataTables.min.js', true);
$PAGE->requires->js_call_amd('local_users/courseallocation', 'init', array());
$PAGE->requires->js_call_amd('local_users/team_approvals', 'init', array());

$PAGE->requires->css('/local/users/css/jquery.dataTables.css');

$teamclass = new myteam();
$courseallocation = new courseallocation();
$teamapprovals = new team_approvals();

echo $OUTPUT->header();

echo html_writer::start_tag('div', array('class' => 'block_team_status block team_status_wrapper'));
echo $teamclass->team_status_view();
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', array('class' => 'roaw'));
echo html_writer::start_tag('div', array('class' => 'block_courseallocation block course_allocation_wrapper col-md-7'));
echo $courseallocation->courseallocation_view();
echo html_writer::end_tag('div');

if(has_capability('local/request:approverecord', $systemcontext)){
	echo html_writer::start_tag('div', array('class' => 'team_approvals col-md-5'));
	echo $teamapprovals->team_approvals_view();
	echo html_writer::end_tag('div');
}

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
?>