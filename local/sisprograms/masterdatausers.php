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
 * @subpackage sisprograms
 * @copyright  2019 S Sarath kumar <sarath@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/local/sisprograms/lib.php');
global $CFG, $PAGE;

$PAGE->requires->jquery();

$PAGE->requires->js('/local/sisprograms/js/jquery.dataTables.min.js',true);
$PAGE->requires->js('/local/sisprograms/js/synctable.js',true);
$PAGE->requires->css('/local/sisprograms/css/jquery.dataTables.css');

$currenttab = optional_param('mode', 'masterdatausers', PARAM_RAW);
$myprogram = sisprograms::getInstance();
$systemcontext = context_system::instance();
//get the admin layout
$PAGE->set_pagelayout('admin');
//check the context level of the user and check weather the user is login to the system or not
$PAGE->set_context($systemcontext);
require_login();
if ($CFG->forcelogin) {
    require_login();
} else {
    user_accesstime_log();
}
//If the loggedin user have the capability of managing the batches allow the page
$capabilities_array = $myprogram->sisprogram_capabilities(); 
if (!has_any_capability($capabilities_array, $systemcontext)) {
    print_cobalterror('permissions_error', 'local_collegestructure');
}
$PAGE->set_url('/local/sisprograms/masterdatausers.php');
$PAGE->set_title(get_string('sisprograms', 'local_sisprograms'));
//Header and the navigation bar
// $PAGE->set_heading(get_string('sisprograms', 'local_sisprograms'));
$PAGE->set_heading(get_string('masterusersdata', 'local_sisprograms'));

$PAGE->navbar->add(get_string('pluginname', 'local_sisprograms'), new moodle_url('/local/sisprograms/index.php'));
$PAGE->navbar->add(get_string('sisprogramusers', 'local_sisprograms'));

$myprogram = sisprograms::getInstance();

echo $OUTPUT->header();
// echo $OUTPUT->heading(get_string('sisprogramusers', 'local_sisprograms'));

$currenttab = 'masterdatausers';
$myprogram->createtabview($currenttab);
echo html_writer::link(new moodle_url('/local/sisprograms/uploadusers.php'),'Back to Enrolments',array('id'=>'masterdatabackbutton', 'style' => 'float:right;'));

$table = new html_table();
$table->id = 'sismasterusersdata';
$table->head = array('First name', 'Last name','PRN Number','Email', 'University', 'Role');
$table->align = array('left', 'left','left','left', 'left', 'left');
echo html_writer::table($table);

echo $OUTPUT->footer();
?>
<style>
    table#sismasterusersdata tr td{text-align: center;}
    table#sismasterusersdata tr td:nth-child(2){text-align: left;}
    table#sismasterusersdata tr td:nth-child(4){text-align: left;}
    .dataTables_length {
        width: 70% !important;
        float: left;
    }
</style>