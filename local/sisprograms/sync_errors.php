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
 * @package    local
 * @subpackage sisprograms
 * @copyright  sarath <sarath@eabyas.in>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once('lib.php');

global $DB, $PAGE,$CFG,$OUTPUT;
$PAGE->requires->jquery();

$PAGE->requires->js('/local/sisprograms/js/jquery.dataTables.min.js',true);
$PAGE->requires->js('/local/sisprograms/js/synctable.js',true);
$PAGE->requires->css('/local/sisprograms/css/jquery.dataTables.css');

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/sisprograms/sync_errors.php');
$PAGE->set_pagelayout('admin');
$strheading = 'Upload Enrolment Sync Errors';
$PAGE->set_title(get_string('sisprograms', 'local_sisprograms'));
require_login();
$PAGE->navbar->add(get_string('pluginname', 'local_sisprograms'), new moodle_url('/local/sisprograms/index.php'));
$PAGE->navbar->add('Upload Enrolment Sync Errors');
$PAGE->set_heading('Upload Enrolment Sync Errors');
$myprogram = sisprograms::getInstance();

echo $OUTPUT->header();

// echo $OUTPUT->heading('Upload Enrolment Sync Errors');

//If the loggedin user have the capability of managing the batches allow the page
$capabilities_array =$myprogram->sisprogram_capabilities(); 
if (!has_any_capability($capabilities_array, $systemcontext)) {
    print_cobalterror('permissions_error', 'local_collegestructure');
}

	$currenttab = 'syncerrors';
    $myprogram->createtabview($currenttab);

echo html_writer::link(new moodle_url('/local/sisprograms/upload.php'),'Back to Upload',array('id'=>'masterdatabackbutton', 'style' => 'float:right;'));

$table = new html_table();
$table->id = 'errors';
$table->head = array('PRN Id', 'Email','firstname','lastname', 'Mandatory Fields', 'Error', 'Sync Excuted By','Sync Excuted Date');
$table->align = array('center', 'left','left','left', 'center', 'left', 'center','center');
echo html_writer::table($table);


echo $OUTPUT->footer();
 
?>
<style>
	table#errors tr td{text-align: center;}
	table#errors tr td:nth-child(2){text-align: left;}
	table#errors tr td:nth-child(4){text-align: left;}
	.dataTables_length {
		float: left;
	}
</style>